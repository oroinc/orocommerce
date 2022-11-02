<?php

namespace Oro\Bundle\CMSBundle\Provider;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\DraftBundle\Provider\DraftableFieldsExclusionProviderInterface;

/**
 * Exclude field names content_style and content_properties from the draftable fields confirmation message
 */
class DraftableFieldsExclusionProvider implements DraftableFieldsExclusionProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function isSupport(string $className): bool
    {
        return Page::class === $className;
    }

    /**
     * {@inheritdoc}
     */
    public function getExcludedFields(): array
    {
        return [
            'content_style',
            'content_properties',
        ];
    }
}
