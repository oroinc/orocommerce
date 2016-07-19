<?php

namespace OroB2B\Bundle\CheckoutBundle\WorkflowState\Mapper;

class CheckoutStateDiffMapperRegistry
{
    /** @var CheckoutStateDiffMapperInterface[] */
    protected $mappers = [];

    /**
     * @param CheckoutStateDiffMapperInterface $mapper
     */
    public function addMapper(CheckoutStateDiffMapperInterface $mapper)
    {
        $this->mappers[$mapper->getName()] = $mapper;
    }

    /**
     * @param string $name
     * @return CheckoutStateDiffMapperInterface
     * @throws \InvalidArgumentException
     */
    public function getMapper($name)
    {
        $name = (string) $name;

        if (array_key_exists($name, $this->mappers)) {
            return $this->mappers[$name];
        }

        throw new \InvalidArgumentException(
            sprintf(
                'Mapper "%s" is missing. Registered mappers are "%s"',
                $name,
                implode(', ', array_keys($this->mappers))
            )
        );
    }

    /**
     * @return CheckoutStateDiffMapperInterface[]
     */
    public function getMappers()
    {
        return $this->mappers;
    }
}
