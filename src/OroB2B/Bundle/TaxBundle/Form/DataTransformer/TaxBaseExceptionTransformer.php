<?php

namespace OroB2B\Bundle\TaxBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;

use OroB2B\Bundle\TaxBundle\Model\TaxBaseException;

class TaxBaseExceptionTransformer implements DataTransformerInterface
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * PriceListSystemConfigSubscriber constructor.
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

        $countryIds = array_unique(array_column($values, 'country'));
        /** @var Country[] $countriesRaw */
        $countriesRaw = $this->doctrineHelper
            ->getEntityRepository('OroAddressBundle:Country')
            ->findBy(['iso2Code' => $countryIds]);

        $countries = [];
        foreach ($countriesRaw as $country) {
            $countries[$country->getIso2Code()] = $country;
        }

        $regionIds = array_unique(array_column($values, 'region'));
        /** @var Region[] $regionsRaw */
        $regionsRaw = $this->doctrineHelper
            ->getEntityRepository('OroAddressBundle:Region')
            ->findBy(['combinedCode' => $regionIds]);

        $regions = [];
        foreach ($regionsRaw as $region) {
            $regions[$region->getCombinedCode()] = $region;
        }

        usort(
            $values,
            function ($a, $b) {
                if ($a['country'] != $b['country']) {
                    return ($a['country'] < $b['country']) ? -1 : 1;
                } else {
                    return ($a['region'] < $b['region']) ? -1 : 1;
                }
            }
        );

        $entities = [];
        foreach ($values as $value) {
            $entity = new TaxBaseException();
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
     * @param TaxBaseException[]|array $entities
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
