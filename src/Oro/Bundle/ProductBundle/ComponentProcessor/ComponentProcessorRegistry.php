<?php

namespace Oro\Bundle\ProductBundle\ComponentProcessor;

use Psr\Container\ContainerInterface;

/**
 * Contains all component processors and allows to get a component processor by its name.
 */
class ComponentProcessorRegistry
{
    /** @var string[] */
    private array $processorNames;
    private ContainerInterface $processorContainer;

    /**
     * @param string[]           $processorNames
     * @param ContainerInterface $processorContainer
     */
    public function __construct(array $processorNames, ContainerInterface $processorContainer)
    {
        $this->processorNames = $processorNames;
        $this->processorContainer = $processorContainer;
    }

    public function getProcessor(string $name): ComponentProcessorInterface
    {
        if (!$this->processorContainer->has($name)) {
            throw new \LogicException(sprintf('Cannot find a processor with the name "%s".', $name));
        }

        return $this->processorContainer->get($name);
    }

    public function hasProcessor(string $name): bool
    {
        return $this->processorContainer->has($name);
    }

    public function hasAllowedProcessors(): bool
    {
        foreach ($this->processorNames as $name) {
            /** @var ComponentProcessorInterface $processor */
            $processor = $this->processorContainer->get($name);
            if ($processor->isAllowed()) {
                return true;
            }
        }

        return false;
    }

    public function getAllowedProcessorsNames(): array
    {
        $result = [];

        foreach ($this->processorNames as $name) {
            /** @var ComponentProcessorInterface $processor */
            $processor = $this->processorContainer->get($name);
            if ($processor->isAllowed()) {
                $result[] = $name;
            }
        }

        return $result;
    }
}
