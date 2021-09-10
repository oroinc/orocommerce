<?php

namespace Oro\Bundle\CheckoutBundle\WorkflowState\Mapper;

use Symfony\Contracts\Service\ResetInterface;

/**
 * The registry of checkout state diff mappers.
 */
class CheckoutStateDiffMapperRegistry implements ResetInterface
{
    /** @var iterable|CheckoutStateDiffMapperInterface[] */
    private $mappers;

    /** @var CheckoutStateDiffMapperInterface[]|null [name => mapper, ...] */
    private $initializedMappers;

    /**
     * @param iterable|CheckoutStateDiffMapperInterface[] $mappers
     */
    public function __construct(iterable $mappers)
    {
        $this->mappers = $mappers;
    }

    /**
     * @throws \InvalidArgumentException if a mapper with the given name does not exist
     */
    public function getMapper(string $name): CheckoutStateDiffMapperInterface
    {
        $mappers = $this->getMappers();
        if (!isset($mappers[$name])) {
            throw new \InvalidArgumentException(sprintf(
                'Mapper "%s" is missing. Registered mappers: %s.',
                $name,
                implode(', ', array_keys($mappers))
            ));
        }

        return $mappers[$name];
    }

    /**
     * @return CheckoutStateDiffMapperInterface[] [name => mapper, ...]
     */
    public function getMappers(): array
    {
        if (null === $this->initializedMappers) {
            $this->initializedMappers = [];
            foreach ($this->mappers as $mapper) {
                $this->initializedMappers[$mapper->getName()] = $mapper;
            }
        }

        return $this->initializedMappers;
    }

    /**
     * {@inheritDoc}
     */
    public function reset()
    {
        $this->initializedMappers = null;
    }
}
