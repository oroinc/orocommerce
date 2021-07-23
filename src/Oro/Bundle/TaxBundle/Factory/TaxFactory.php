<?php

namespace Oro\Bundle\TaxBundle\Factory;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\TaxBundle\Mapper\TaxMapperInterface;
use Oro\Bundle\TaxBundle\Mapper\UnmappableArgumentException;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * The factory that creates {@see \Oro\Bundle\TaxBundle\Model\Taxable} objects.
 */
class TaxFactory
{
    /** @var ContainerInterface */
    private $mappers;

    public function __construct(ContainerInterface $mappers)
    {
        $this->mappers = $mappers;
    }

    /**
     * @param object $object
     *
     * @return Taxable
     */
    public function create($object)
    {
        return $this->getMapper($object)->map($object);
    }

    /**
     * @param object $object
     *
     * @return bool
     */
    public function supports($object)
    {
        return $this->mappers->has(ClassUtils::getClass($object));
    }

    /**
     * @param object $object
     *
     * @return TaxMapperInterface
     *
     * @throws UnmappableArgumentException if Tax Mapper for $object can't be found
     */
    private function getMapper($object): TaxMapperInterface
    {
        $className = ClassUtils::getClass($object);
        try {
            return $this->mappers->get($className);
        } catch (NotFoundExceptionInterface $e) {
            throw new UnmappableArgumentException(
                sprintf('Can\'t find Tax Mapper for object "%s"', $className)
            );
        }
    }
}
