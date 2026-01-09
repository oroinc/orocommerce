<?php

namespace Oro\Bundle\TaxBundle\Form\DataTransformer;

use Oro\Bundle\TaxBundle\Factory\TaxBaseExclusionFactory;
use Oro\Bundle\TaxBundle\Model\TaxBaseExclusion;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * Transforms tax base exclusion data between array and entity representations.
 */
class TaxBaseExclusionTransformer implements DataTransformerInterface
{
    /**
     * @var TaxBaseExclusionFactory
     */
    protected $taxBaseExclusionFactory;

    public function __construct(TaxBaseExclusionFactory $taxBaseExclusionFactory)
    {
        $this->taxBaseExclusionFactory = $taxBaseExclusionFactory;
    }

    /**
     * @param array $values
     */
    #[\Override]
    public function transform($values): mixed
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
     * @param TaxBaseExclusion[] $entities
     */
    #[\Override]
    public function reverseTransform($entities): mixed
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
