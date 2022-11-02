<?php

namespace Oro\Bundle\CMSBundle\Parser;

use Twig\Environment;
use Twig\Node\Expression\FunctionExpression;
use Twig\Node\Node;
use Twig\Source;

/**
 * Find specific nodes in twig templates
 */
class TwigParser
{
    /** @var Environment */
    private $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    /**
     * Find all calls of the specific functions in specific template
     *
     * @param string|null $content
     * @param string[] $functionNames
     *
     * @return array ['functionName' => [['arg1', 'arg2', ...], ['arg1', 'arg2', ...], ...], ...]
     */
    public function findFunctionCalls(?string $content, array $functionNames): array
    {
        if (!$content || !$functionNames) {
            return [];
        }

        $source = new Source($content, 'content');

        $nodeList = [
            $this->twig->parse(
                $this->twig->tokenize($source)
            )
        ];

        $response = [];
        while (\count($nodeList)) {
            $nodeToParse = $nodeList;
            $nodeList = [];

            foreach ($nodeToParse as $i => $node) {
                if ($node instanceof FunctionExpression && $node->hasAttribute('name')) {
                    $name = $node->getAttribute('name');
                    if (\in_array($name, $functionNames, false)) {
                        $response[$name][] = $this->parseArguments($node);
                    }
                }

                foreach ($node as $childNode) {
                    $nodeList[] = $childNode;
                }
            }
        }

        return $response;
    }

    private function parseArguments(Node $node): array
    {
        $arguments = [];
        if ($node->hasNode('arguments')) {
            $argumentsNode = $node->getNode('arguments');

            /** @var Node $argumentNode */
            foreach ($argumentsNode as $argumentNode) {
                $arguments[] = $argumentNode->hasAttribute('value')
                    ? (string)$argumentNode->getAttribute('value')
                    : null;
            }
        }

        return $arguments;
    }
}
