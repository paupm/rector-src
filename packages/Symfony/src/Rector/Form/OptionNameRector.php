<?php

declare(strict_types=1);

namespace Rector\Symfony\Rector\Form;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Scalar\String_;
use Rector\Rector\AbstractRector;
use Rector\RectorDefinition\CodeSample;
use Rector\RectorDefinition\RectorDefinition;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @see \Rector\Symfony\Tests\Rector\Form\OptionNameRector\OptionNameRectorTest
 */
final class OptionNameRector extends AbstractRector
{
    /**
     * @var string[]
     */
    private $oldToNewOption = [
        'precision' => 'scale',
        'virtual' => 'inherit_data',
    ];

    public function getDefinition(): RectorDefinition
    {
        return new RectorDefinition('Turns old option names to new ones in FormTypes in Form in Symfony', [
            new CodeSample(
<<<'PHP'
$builder = new FormBuilder;
$builder->add("...", ["precision" => "...", "virtual" => "..."];
PHP
                ,
<<<'PHP'
$builder = new FormBuilder;
$builder->add("...", ["scale" => "...", "inherit_data" => "..."];
PHP
            ),
        ]);
    }

    /**
     * @return string[]
     */
    public function getNodeTypes(): array
    {
        return [MethodCall::class];
    }

    /**
     * @param MethodCall $node
     */
    public function refactor(Node $node): ?Node
    {
        if (! $this->isName($node->name, 'add')) {
            return null;
        }

        if (! $this->isObjectType($node->var, FormBuilderInterface::class)) {
            return null;
        }

        if (! isset($node->args[2])) {
            return null;
        }

        $optionsNode = $node->args[2]->value;
        if (! $optionsNode instanceof Array_) {
            return null;
        }

        foreach ($optionsNode->items as $arrayItemNode) {
            if (! $arrayItemNode->key instanceof String_) {
                continue;
            }

            $this->processStringKey($arrayItemNode->key);
        }

        return $node;
    }

    private function processStringKey(String_ $stringKeyNode): void
    {
        $currentOptionName = $stringKeyNode->value;

        $stringKeyNode->value = $this->oldToNewOption[$currentOptionName] ?? $stringKeyNode->value;
    }
}
