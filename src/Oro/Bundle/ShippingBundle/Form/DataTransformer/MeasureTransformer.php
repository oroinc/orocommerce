<?php

namespace Oro\Bundle\ShippingBundle\Form\DataTransformer;

use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\ProductBundle\Entity\MeasureUnitInterface;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * Transforms measure units between entity and code representations.
 */
class MeasureTransformer implements DataTransformerInterface
{
    /** @var ObjectRepository */
    protected $repository;

    public function __construct(ObjectRepository $repository)
    {
        $this->repository = $repository;
    }

    #[\Override]
    public function transform($values): mixed
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

    #[\Override]
    public function reverseTransform($entities): mixed
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
