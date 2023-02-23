<?php

namespace Oro\Bundle\CMSBundle\Provider;

use Oro\Bundle\CMSBundle\ContentWidget\ContentWidgetTypeRegistry;

/**
 * Provides pairs [<label> => <name>] of available content widget types.
 */
class ContentWidgetTypeProvider
{
    private ContentWidgetTypeRegistry $contentWidgetTypeRegistry;

    public function __construct(ContentWidgetTypeRegistry $contentWidgetTypeRegistry)
    {
        $this->contentWidgetTypeRegistry = $contentWidgetTypeRegistry;
    }

    public function getAvailableContentWidgetTypes(): array
    {
        $types = [];
        foreach ($this->contentWidgetTypeRegistry->getTypes() as $type) {
            $types[$type->getLabel()] = $type::getName();
        }

        return $types;
    }
}
