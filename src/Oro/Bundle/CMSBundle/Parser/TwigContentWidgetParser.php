<?php

namespace Oro\Bundle\CMSBundle\Parser;

use Twig\Environment;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\FunctionExpression;
use Twig\Node\Node;
use Twig\Source;

/**
 * Allows to read a list of all used widgets from the twig content.
 */
class TwigContentWidgetParser
{
    /** @var Environment */
    private $twig;

    /**
     * @param Environment $twig
     */
    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    /**
     * @param string|null $content
     * @return array
     */
    public function parseNames(?string $content): array
    {
        if (!$content) {
            return [];
        }

        $source = new Source($content, 'content');

        return array_unique($this->parseNode($this->twig->parse($this->twig->tokenize($source))));
    }

    /**
     * @param Node $node
     * @return array
     */
    private function parseNode(Node $node): array
    {
        $names = [[]];

        if ($node instanceof FunctionExpression &&
            $node->hasAttribute('name') &&
            $node->getAttribute('name') === 'widget'
        ) {
            $name = $this->processFunctionNode($node);
            if ($name) {
                $names[] = [$name];
            }
        }

        foreach ($node as $childNode) {
            $names[] = $this->parseNode($childNode);
        }

        return array_merge(...$names);
    }

    /**
     * @param FunctionExpression $node
     * @return string
     */
    private function processFunctionNode(FunctionExpression $node): string
    {
        if (!$node->hasNode('arguments')) {
            return '';
        }

        $argumentsNode = $node->getNode('arguments');
        if (!$argumentsNode->hasNode(0)) {
            return '';
        }

        $nameNode = $argumentsNode->getNode(0);
        if (!$nameNode instanceof ConstantExpression || !$nameNode->hasAttribute('value')) {
            return '';
        }

        return (string) $nameNode->getAttribute('value');
    }
}
