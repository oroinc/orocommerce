<?php

namespace Oro\Bundle\TaxBundle\Factory;

use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\TaxBundle\Mapper\TaxMapperInterface;
use Oro\Bundle\TaxBundle\Mapper\UnmappableArgumentException;
use Oro\Bundle\TaxBundle\Model\Taxable;

class TaxFactory
{
    /**
     * @var TaxMapperInterface[]
     */
    protected $mappers = [];

    /**
     * Add Tax Mapper
     *
     * @param TaxMapperInterface $mapper
     */
    public function addMapper(TaxMapperInterface $mapper)
    {
        $this->mappers[$mapper->getProcessingClassName()] = $mapper;
    }

    /**
     * @param object $object
     * @return Taxable
     */
    public function create($object)
    {
        return $this->getMapper($object)->map($object);
    }

    /**
     * @param object $object
     * @return TaxMapperInterface
     * @throws UnmappableArgumentException if Tax Mapper for $object can't be found
     */
    protected function getMapper($object)
    {
        $objectClassName = ClassUtils::getClass($object);
        if (!array_key_exists($objectClassName, $this->mappers)) {
            throw new UnmappableArgumentException(
                sprintf('Can\'t find Tax Mapper for object "%s"', $objectClassName)
            );
        }

        return $this->mappers[$objectClassName];
    }

    /**
     * @param object $object
     * @return bool
     */
    public function supports($object)
    {
        try {
            $this->getMapper($object);

            return true;
        } catch (UnmappableArgumentException $e) {
        }

        return false;
    }
}
