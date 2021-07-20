<?php

namespace Oro\Bundle\PricingBundle\Provider;

use Oro\Bundle\CurrencyBundle\Provider\CurrencyProviderInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WebsiteBundle\Entity\Repository\WebsiteRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class WebsiteCurrencyProvider
{
    /**
     * @var CurrencyProviderInterface
     */
    protected $currencyProvider;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    public function __construct(CurrencyProviderInterface $currencyProvider, DoctrineHelper $doctrineHelper)
    {
        $this->currencyProvider = $currencyProvider;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param int $websiteId
     * @return string
     */
    public function getWebsiteDefaultCurrency($websiteId)
    {
        return $this->currencyProvider->getDefaultCurrency();
    }

    /**
     * @return array
     */
    public function getAllWebsitesCurrencies()
    {
        /** @var WebsiteRepository $websiteRepo */
        $websiteRepo = $this->doctrineHelper->getEntityRepository(Website::class);

        $currencies = [];
        foreach ($websiteRepo->getWebsiteIdentifiers() as $websiteId) {
            $currencies[$websiteId] = $this->getWebsiteDefaultCurrency($websiteId);
        }

        return $currencies;
    }
}
