<?php

namespace OroB2B\Bundle\TaxBundle\Factory;

use Doctrine\Common\Util\ClassUtils;

use OroB2B\Bundle\TaxBundle\Mapper\TaxMapperInterface;
use OroB2B\Bundle\TaxBundle\Mapper\UnmappableArgumentException;
use OroB2B\Bundle\TaxBundle\Model\Taxable;

class TaxFactory
{
    /**
     * @var TaxMapperInterface[]
     */
    protected $mappers = [];

    /**
     * @var Taxable[]
     */
    protected $taxable = [];

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
     * @throws UnmappableArgumentException if Tax Mapper for $object can't be found
     */
    public function create($object)
    {
        $oid = spl_object_hash($object);

        if (array_key_exists($oid, $this->taxable)) {
            return $this->taxable[$oid];
        }

        $objectClassName = ClassUtils::getClass($object);
        if (!array_key_exists($objectClassName, $this->mappers)) {
            throw new UnmappableArgumentException(
                sprintf('Can\'t find Tax Mapper for object "%s"', $objectClassName)
            );
        }

        $this->taxable[$oid] = $this->mappers[$objectClassName]->map($object);

        return $this->taxable[$oid];
    }
}
