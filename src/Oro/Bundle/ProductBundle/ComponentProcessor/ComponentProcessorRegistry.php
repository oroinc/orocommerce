<?php

namespace Oro\Bundle\ProductBundle\ComponentProcessor;

/**
 * Contains all component processors and allows to get a component processor by its name.
 */
class ComponentProcessorRegistry
{
    /** @var ComponentProcessorInterface[] */
    private array $processors = [];

    public function addProcessor(ComponentProcessorInterface $processor): void
    {
        $this->processors[$processor->getName()] = $processor;
    }

    /**
     * @return ComponentProcessorInterface[]
     */
    public function getProcessors(): array
    {
        return $this->processors;
    }

    public function getProcessorByName(string $name): ?ComponentProcessorInterface
    {
        return $this->processors[$name] ?? null;
    }

    public function hasProcessor(string $name): bool
    {
        return isset($this->processors[$name]);
    }

    public function hasAllowedProcessor(): bool
    {
        $hasAllowed = false;
        foreach ($this->processors as $processor) {
            if ($processor->isAllowed()) {
                $hasAllowed = true;
                break;
            }
        }
        return $hasAllowed;
    }
}
