<?php

declare(strict_types=1);

namespace Rector\Console\Option;

use Nette\Utils\ObjectHelpers;
use Nette\Utils\Strings;
use Rector\Exception\Configuration\SetNotFoundException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symplify\PackageBuilder\Configuration\ConfigFileFinder;

final class SetOptionResolver
{
    /**
     * @var string
     */
    private $keyName;

    /**
     * @var string[]
     */
    private $optionNames = [];

    /**
     * @param string[] $optionNames
     */
    public function __construct(array $optionNames = ['--set', '-s'], string $keyName = 'set')
    {
        $this->optionNames = $optionNames;
        $this->keyName = $keyName;
    }

    public function detectFromInputAndDirectory(InputInterface $input, string $configDirectory): ?string
    {
        $setName = ConfigFileFinder::getOptionValue($input, $this->optionNames);
        if ($setName === null) {
            return null;
        }

        $nearestMatches = $this->findNearestMatchingFiles($configDirectory, $setName);
        if (count($nearestMatches) === 0) {
            $this->reportSetNotFound($configDirectory, $setName);
        }

        /** @var SplFileInfo $nearestMatch */
        $nearestMatch = array_shift($nearestMatches);

        return $nearestMatch->getRealPath();
    }

    private function reportSetNotFound(string $configDirectory, string $setName): void
    {
        $allSets = $this->findAllSetsInDirectory($configDirectory);

        $suggestedSet = ObjectHelpers::getSuggestion($allSets, $setName);

        $hasSetVersion = (bool) Strings::match($setName, '#[\d]#');

        [$versionedSets, $unversionedSets] = $this->separateVersionedAndUnversionedSets($allSets);

        $setsListInString = $this->createSetListInString($hasSetVersion, $unversionedSets, $versionedSets);

        $setNotFoundMessage = sprintf(
            '%s "%s" was not found.%s%s',
            ucfirst($this->keyName),
            $setName,
            PHP_EOL,
            $suggestedSet ? sprintf('Did you mean "%s"?', $suggestedSet) . PHP_EOL : 'Pick one of above.'
        );

        $pickOneOfMessage = sprintf('Pick "--%s" of:%s%s', $this->keyName, PHP_EOL . PHP_EOL, $setsListInString);

        throw new SetNotFoundException($setNotFoundMessage . PHP_EOL . $pickOneOfMessage);
    }

    /**
     * @return string[]
     */
    private function findAllSetsInDirectory(string $configDirectory): array
    {
        $finder = Finder::create()
            ->files()
            ->in($configDirectory);

        $sets = [];
        foreach ($finder->getIterator() as $fileInfo) {
            $sets[] = $fileInfo->getBasename('.' . $fileInfo->getExtension());
        }

        sort($sets);

        return array_unique($sets);
    }

    /**
     * @return SplFileInfo[]
     */
    private function findNearestMatchingFiles(string $configDirectory, string $setName): array
    {
        $configFiles = Finder::create()
            ->files()
            ->in($configDirectory)
            ->getIterator();

        $nearestMatches = [];

        $setName = Strings::lower($setName);

        // the version must match, so 401 is not compatible with 40
        $setVersion = $this->matchVersionInTheEnd($setName);

        foreach ($configFiles as $configFile) {
            // only similar configs, not too far
            // this allows to match "Symfony.40" to "symfony40" config
            $fileNameWithoutExtension = pathinfo($configFile->getFilename(), PATHINFO_FILENAME);
            $distance = levenshtein($fileNameWithoutExtension, $setName);
            if ($distance > 2) {
                continue;
            }

            if ($setVersion) {
                $fileVersion = $this->matchVersionInTheEnd($fileNameWithoutExtension);
                if ($setVersion !== $fileVersion) {
                    // not a version match
                    continue;
                }
            }

            $nearestMatches[$distance] = $configFile;
        }

        ksort($nearestMatches);

        return $nearestMatches;
    }

    private function matchVersionInTheEnd(string $setName): ?string
    {
        $match = Strings::match($setName, '#(?<version>[\d\.]+$)#');
        if (! $match) {
            return null;
        }

        $version = $match['version'];
        return Strings::replace($version, '#\.#');
    }

    /**
     * @param string[] $unversionedSets
     * @param string[] $versionedSets
     */
    private function createSetListInString(
        bool $hasSetVersion,
        array $unversionedSets,
        array $versionedSets
    ): string {
        $setsListInString = '';

        if ($hasSetVersion === false) {
            foreach ($unversionedSets as $unversionedSet) {
                $setsListInString .= ' * ' . $unversionedSet . PHP_EOL;
            }
        }

        if ($hasSetVersion) {
            foreach ($versionedSets as $groupName => $configName) {
                $setsListInString .= ' * ' . $groupName . ': ' . implode(', ', $configName) . PHP_EOL;
            }
        }
        return $setsListInString;
    }

    /**
     * @param string[] $allSets
     * @return string[][]
     */
    private function separateVersionedAndUnversionedSets(array $allSets): array
    {
        $versionedSets = [];
        $unversionedSets = [];

        foreach ($allSets as $set) {
            $match = Strings::match($set, '#^[A-Za-z\-]+#');
            if ($match === null) {
                $unversionedSets[] = $set;
            }

            $setWithoutVersion = rtrim($match[0], '-');
            if ($setWithoutVersion !== $set) {
                $versionedSets[$setWithoutVersion][] = $set;
            }
        }

        return [$versionedSets, $unversionedSets];
    }
}
