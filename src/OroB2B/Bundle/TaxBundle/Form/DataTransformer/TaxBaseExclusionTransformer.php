<?php

namespace OroB2B\Bundle\TaxBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

use Oro\Component\PhpUtils\ArrayUtil;

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
        if (empty($values)) {
            return [];
        }

        $countryIds = array_unique(ArrayUtil::arrayColumn($values, 'country'));
        /** @var Country[] $countriesRaw */
        $countriesRaw = $this->doctrineHelper
            ->getEntityRepository('OroAddressBundle:Country')
            ->findBy(['iso2Code' => $countryIds]);

        $countries = [];
        foreach ($countriesRaw as $country) {
            $countries[$country->getIso2Code()] = $country;
        }

        $regionIds = array_unique(ArrayUtil::arrayColumn($values, 'region'));
        /** @var Region[] $regionsRaw */
        $regionsRaw = $this->doctrineHelper
            ->getEntityRepository('OroAddressBundle:Region')
            ->findBy(['combinedCode' => $regionIds]);

        $regions = [];
        foreach ($regionsRaw as $region) {
            $regions[$region->getCombinedCode()] = $region;
        }

        $entities = [];
        foreach ($values as $value) {
            $entity = new TaxBaseExclusion();
            $entity
                ->setCountry($countries[$value['country']])
                ->setRegion($regions[$value['region']])
                ->setOption($value['option']);
            $entities[] = $entity;
        }

        return $entities;
    }

    /**
     * {@inheritdoc}
     * @param TaxBaseExclusion[]|array $entities
     */
    public function reverseTransform($entities)
    {
        if (empty($entities)) {
            return [];
        }

        $values = [];
        foreach ($entities as $entity) {
            $values[] = [
                'country' => $entity->getCountry()->getIso2Code(),
                'region' => $entity->getRegion()->getCombinedCode(),
                'option' => $entity->getOption(),
            ];
        }

        return $values;
    }
}
