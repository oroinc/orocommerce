<?php

namespace Oro\Bundle\ConsentBundle\SystemConfig;

use Oro\Bundle\ConsentBundle\Entity\Consent;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * Convert config to entities and backwards
 */
class ConsentConfigConverter
{
    const SORT_ORDER_KEY = 'sort_order';
    const CONSENT_KEY = 'consent';

    /** @var RegistryInterface */
    protected $doctrine;

    /**
     * @param RegistryInterface $doctrine
     */
    public function __construct(RegistryInterface $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * @param array $configs
     *
     * @return array
     */
    public function convertBeforeSave(array $configs)
    {
        $result = [];
        /** @var ConsentConfig $consentConfig */
        foreach ($configs as $consentConfig) {
            $consent = $consentConfig->getConsent();
            if ($consent instanceof Consent) {
                $result[] = [
                    self::CONSENT_KEY => $consent->getId(),
                    self::SORT_ORDER_KEY => $consentConfig->getSortOrder(),
                ];
            }
        }

        return $result;
    }

    /**
     * @param array $configs
     *
     * @return ConsentConfig[]
     */
    public function convertFromSaved(array $configs): array
    {
        $consentIds = array_column($configs, self::CONSENT_KEY);

        if (empty($consentIds)) {
            return [];
        }

        $repository = $this->doctrine
            ->getManagerForClass(Consent::class)
            ->getRepository(Consent::class);

        /** @var Consent[] $consents */
        $consents = $repository->findBy(['id' => $consentIds]);
        $configs = array_combine($consentIds, $configs);

        $result = [];
        foreach ($consents as $consent) {
            $sortOrder = $configs[$consent->getId()][self::SORT_ORDER_KEY] ?? PHP_INT_MAX;

            $result[] = new ConsentConfig(
                $consent,
                $sortOrder
            );
        }

        $this->restoreSortOrder($result);

        return $result;
    }

    /**
     * @param ConsentConfig[] $consentConfigs
     */
    protected function restoreSortOrder(array &$consentConfigs)
    {
        usort($consentConfigs, function (ConsentConfig $consentConfig1, ConsentConfig $consentConfig2) {
            return $consentConfig1->getSortOrder() <=> $consentConfig2->getSortOrder();
        });
    }
}
