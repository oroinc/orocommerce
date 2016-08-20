<?php

namespace Oro\Bundle\TaxBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

use Oro\Bundle\TaxBundle\Model\TaxBaseExclusion;
use Oro\Bundle\TaxBundle\Factory\TaxBaseExclusionFactory;

class TaxBaseExclusionTransformer implements DataTransformerInterface
{
    /**
     * @var TaxBaseExclusionFactory
     */
    protected $taxBaseExclusionFactory;

    /**
     * @param TaxBaseExclusionFactory $taxBaseExclusionFactory
     */
    public function __construct(TaxBaseExclusionFactory $taxBaseExclusionFactory)
    {
        $this->taxBaseExclusionFactory = $taxBaseExclusionFactory;
    }

    /**
     * {@inheritdoc}
     * @param array $values
     */
    public function transform($values)
    {
        if (empty($values) || !is_array($values)) {
            return [];
        }

        $entities = [];
        foreach ($values as $value) {
            $entities[] = $this->taxBaseExclusionFactory->create($value);
        }

        return $entities;
    }

    /**
     * {@inheritdoc}
     * @param TaxBaseExclusion[] $entities
     */
    public function reverseTransform($entities)
    {
        if (empty($entities) || !is_array($entities)) {
            return [];
        }

        $values = [];
        /** @var TaxBaseExclusion $entity */
        foreach ($entities as $entity) {
            $values[] = [
                'country' => $entity->getCountry() ? $entity->getCountry()->getIso2Code() : null,
                'region' => $entity->getRegion() ? $entity->getRegion()->getCombinedCode() : null,
                'option' => $entity->getOption(),
            ];
        }

        return $values;
    }
}
