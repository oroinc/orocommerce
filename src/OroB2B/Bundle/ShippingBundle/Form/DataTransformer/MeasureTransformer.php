<?php

namespace Oro\Bundle\ShippingBundle\Form\DataTransformer;

use Doctrine\Common\Persistence\ObjectRepository;

use Symfony\Component\Form\DataTransformerInterface;

use Oro\Bundle\ProductBundle\Entity\MeasureUnitInterface;

class MeasureTransformer implements DataTransformerInterface
{
    /** @var ObjectRepository */
    protected $repository;

    /**
     * @param ObjectRepository $repository
     */
    public function __construct(ObjectRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($values)
    {
        if (!is_array($values)) {
            return [];
        }

        $entities = [];
        foreach ($values as $value) {
            $entities[] = $this->repository->find($value);
        }

        return $entities;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($entities)
    {
        if (!is_array($entities)) {
            return [];
        }

        $values = [];
        foreach ($entities as $entity) {
            if ($entity instanceof MeasureUnitInterface) {
                $values[] = $entity->getCode();
            }
        }

        return $values;
    }
}
