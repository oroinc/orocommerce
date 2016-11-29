<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\PricingBundle\DependencyInjection\Configuration;
use Oro\Bundle\PricingBundle\Entity\MinimalProductPrice;
use Oro\Bundle\PricingBundle\Placeholder\CPLIdPlaceholder;
use Oro\Bundle\PricingBundle\Placeholder\CurrencyPlaceholder;
use Oro\Bundle\PricingBundle\Placeholder\UnitPlaceholder;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Manager\WebsiteContextManager;
use Symfony\Bridge\Doctrine\RegistryInterface;

class WebsiteSearchProductPriceIndexerListener
{
    /**
     * @var WebsiteContextManager
     */
    private $websiteContextManger;

    /**
     * @var RegistryInterface
     */
    private $doctrine;

    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @param WebsiteContextManager $websiteContextManager
     * @param RegistryInterface $doctrine
     * @param ConfigManager $configManager
     */
    public function __construct(
        WebsiteContextManager $websiteContextManager,
        RegistryInterface $doctrine,
        ConfigManager $configManager
    ) {
        $this->websiteContextManger = $websiteContextManager;
        $this->doctrine = $doctrine;
        $this->configManager = $configManager;
    }

    /**
     * @param IndexEntityEvent $event
     */
    public function onWebsiteSearchIndex(IndexEntityEvent $event)
    {
        $websiteId = $this->websiteContextManger->getWebsiteId($event->getContext());
        if (!$websiteId) {
            $event->stopPropagation();

            return;
        }

        $repository = $this->doctrine->getRepository(MinimalProductPrice::class);
        $prices = $repository->findByWebsite(
            $websiteId,
            $event->getEntities(),
            $this->configManager->get(Configuration::getConfigKeyToPriceList())
        );

        foreach ($prices as $price) {
            $event->addPlaceholderField(
                $price['product'],
                'minimum_price_CPL_ID_CURRENCY_UNIT',
                $price['value'],
                [
                    CPLIdPlaceholder::NAME => $price['cpl'],
                    CurrencyPlaceholder::NAME => $price['currency'],
                    UnitPlaceholder::NAME => $price['unit'],
                ]
            );
        }
    }
}
