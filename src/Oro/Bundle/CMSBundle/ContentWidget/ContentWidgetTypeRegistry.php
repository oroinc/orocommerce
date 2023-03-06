<?php

namespace Oro\Bundle\CMSBundle\ContentWidget;

/**
 * The registry for all content widget types registered in the application.
 */
class ContentWidgetTypeRegistry
{
    /** @var iterable<ContentWidgetTypeInterface> */
    private iterable $types;

    /**
     * @param iterable<ContentWidgetTypeInterface> $types
     */
    public function __construct(iterable $types)
    {
        $this->types = $types;
    }

    public function getWidgetType(string $name): ?ContentWidgetTypeInterface
    {
        foreach ($this->types as $type) {
            if ($type::getName() === $name) {
                return $type;
            }
        }

        return null;
    }

    /**
     * @return ContentWidgetTypeInterface[]
     */
    public function getTypes(): array
    {
        return iterator_to_array($this->types);
    }
}
