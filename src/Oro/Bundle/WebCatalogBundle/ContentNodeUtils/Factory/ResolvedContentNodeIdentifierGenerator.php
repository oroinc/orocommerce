<?php

namespace Oro\Bundle\WebCatalogBundle\ContentNodeUtils\Factory;

/**
 * Creates identifier for {@see ResolvedContentNode}.
 */
class ResolvedContentNodeIdentifierGenerator
{
    private const ROOT_NODE_IDENTIFIER = 'root';
    private const IDENTIFIER_GLUE = '__';

    public function getIdentifierByUrl(string $url): string
    {
        if (!$url) {
            return '';
        }

        $url = trim($url, '/');
        $identifierParts = [self::ROOT_NODE_IDENTIFIER];
        if ($url) {
            if (strpos($url, '/') > 0) {
                $identifierParts = array_merge($identifierParts, explode('/', $url));
            } else {
                $identifierParts[] = $url;
            }
        }

        return implode(self::IDENTIFIER_GLUE, $identifierParts);
    }
}
