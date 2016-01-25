<?php

namespace OroB2B\Bundle\TaxBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;

use OroB2B\Bundle\TaxBundle\Model\TaxBaseExclusion;

class TaxBaseExclusionTransformer implements DataTransformerInterface
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
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
            $entity = new TaxBaseExclusion();

            if (!empty($value['country'])) {
                /** @var Country $country */
                $country = $this->doctrineHelper->getEntityReference('OroAddressBundle:Country', $value['country']);
                $entity->setCountry($country);
            }

            if (!empty($value['region'])) {
                /** @var Region $region */
                $region = $this->doctrineHelper->getEntityReference('OroAddressBundle:Region', $value['region']);
                $entity->setRegion($region);
            }

            if (!empty($value['option'])) {
                $entity->setOption($value['option']);
            }

            $entities[] = $entity;
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
