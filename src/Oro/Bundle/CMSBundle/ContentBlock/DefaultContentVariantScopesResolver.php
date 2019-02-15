<?php

namespace Oro\Bundle\CMSBundle\ContentBlock;

use Oro\Bundle\CMSBundle\Entity\ContentBlock;

/**
 * Resolve scopes for default content variant of Content Block.
 */
class DefaultContentVariantScopesResolver
{
    /**
     * @param ContentBlock $contentBlock
     */
    public function resolve(ContentBlock $contentBlock)
    {
        $defaultVariant = $contentBlock->getDefaultVariant();

        if ($defaultVariant) {
            $defaultVariant->resetScopes();
        }
    }
}
