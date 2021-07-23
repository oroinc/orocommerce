<?php

namespace Oro\Bundle\CMSBundle\ContentWidget;

/**
 * Registry of all content widgets registereg in the application.
 */
class ContentWidgetTypeRegistry
{
    /** @var \IteratorAggregate|ContentWidgetTypeInterface[] */
    private $types;

    /**
     * @param \IteratorAggregate|ContentWidgetTypeInterface[] $types
     */
    public function __construct(\IteratorAggregate $types)
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
        return iterator_to_array($this->types->getIterator());
    }
}
