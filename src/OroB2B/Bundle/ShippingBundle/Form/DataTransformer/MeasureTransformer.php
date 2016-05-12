<?php

namespace OroB2B\Bundle\ShippingBundle\Form\DataTransformer;

use Doctrine\Common\Persistence\ObjectRepository;

use Symfony\Component\Form\DataTransformerInterface;

use OroB2B\Bundle\ProductBundle\Entity\MeasureUnitInterface;

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
        if (empty($values) || !is_array($values)) {
            return [];
        }

        $entities = [];
        foreach ($values as $value) {
            $entities[] = $this->repository->findOneBy(['code' => $value]);
        }

        return $entities;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($entities)
    {
        if (empty($entities) || !is_array($entities)) {
            return [];
        }

        $values = [];
        /** @var MeasureUnitInterface[] $entities */
        foreach ($entities as $entity) {
            $values[] = $entity->getCode();
        }

        return $values;
    }
}
