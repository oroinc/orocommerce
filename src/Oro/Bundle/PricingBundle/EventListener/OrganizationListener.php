<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CurrencyBundle\Config\DefaultCurrencyConfigProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerTrait;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\SystemConfig\PriceListConfig;
use Oro\Bundle\PricingBundle\SystemConfig\PriceListConfigConverter;

/**
 * Creates default price list during organization creation.
 */
class OrganizationListener implements OptionalListenerInterface
{
    private const PRICE_LIST_NAME_POSTFIX = 'Price List';

    use OptionalListenerTrait;

    private DoctrineHelper $doctrineHelper;
    private DefaultCurrencyConfigProvider $currencyConfigProvider;
    private PriceListConfigConverter $configConverter;
    private ConfigManager $configManager;

    private array $createdPrices = [];

    public function __construct(
        DoctrineHelper $doctrineHelper,
        DefaultCurrencyConfigProvider $currencyConfigProvider,
        PriceListConfigConverter $configConverter,
        ConfigManager $configManager
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->currencyConfigProvider = $currencyConfigProvider;
        $this->configConverter = $configConverter;
        $this->configManager = $configManager;
    }

    public function prePersist(Organization $organization): void
    {
        if (!$this->enabled) {
            return;
        }

        $priceList = new PriceList();
        $priceList->setActive(true);
        $priceList->setName($organization->getName() . ' ' . self::PRICE_LIST_NAME_POSTFIX);
        $priceList->setOrganization($organization);
        $priceList->setCurrencies($this->currencyConfigProvider->getCurrencyList());
        $this->doctrineHelper->getEntityManager(PriceList::class)->persist($priceList);

        $this->createdPrices[] = $priceList;
    }

    public function postFlush(): void
    {
        if (!$this->enabled) {
            return;
        }

        if (!\count($this->createdPrices)) {
            return;
        }

        $configs = $this->configConverter->convertFromSaved(
            $this->configManager->get('oro_pricing.default_price_lists')
        );

        foreach ($this->createdPrices as $defaultPriceList) {
            $configs[] = new PriceListConfig($defaultPriceList, 100, true);
        }

        $this->configManager->set('oro_pricing.default_price_lists', $configs);
        $this->configManager->flush();

        $this->createdPrices = [];
    }
}
