<?php

namespace Oro\Bundle\CMSBundle\ContentWidget;

/**
 * Registry of all content widgets registereg in the application.
 */
class ContentWidgetTypeRegistry
{
    /** @var ContentWidgetTypeInterface[] */
    private $types;

    /**
     * @param ContentWidgetTypeInterface[] $types
     */
    public function __construct(iterable $types)
    {
        $this->types = $types;
    }

    /**
     * @param string $name
     * @return ContentWidgetTypeInterface|null
     */
    public function getWidgetType(string $name): ?ContentWidgetTypeInterface
    {
        foreach ($this->types as $type) {
            if ($type::getName() === $name) {
                return $type;
            }
        }

        return null;
    }
}
