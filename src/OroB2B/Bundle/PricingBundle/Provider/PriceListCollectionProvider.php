<?php

namespace OroB2B\Bundle\PricingBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;

use Doctrine\Common\Persistence\ObjectRepository;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\PricingBundle\SystemConfig\PriceListConfig;
use OroB2B\Bundle\PricingBundle\SystemConfig\PriceListConfigConverter;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class PriceListCollectionProvider
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /** @var  ConfigManager */
    protected $configManager;

    /** @var  PriceListConfigConverter */
    protected $configConverter;


    /**
     * @param ManagerRegistry $registry
     * @param ConfigManager $configManager
     * @param PriceListConfigConverter $configConverter
     */
    public function __construct(
        ManagerRegistry $registry,
        ConfigManager $configManager,
        PriceListConfigConverter $configConverter
    ) {
        $this->registry = $registry;
        $this->configManager = $configManager;
        $this->configConverter = $configConverter;
    }

    /**
     * @return PriceListSequenceMember[]
     */
    public function getPriceListsByConfig()
    {
        /** @var PriceListConfig[] $priceListsConfig */
        $priceListsConfig = $this->configConverter->convertFromSaved(
            $this->configManager->get('oro_b2b_pricing.default_price_lists')
        );
        $priceListCollection = [];
        foreach ($priceListsConfig as $priceListConfig) {
            $priceListCollection[] = new PriceListSequenceMember(
                $priceListConfig->getPriceList(),
                $priceListConfig->isMergeAllowed()
            );
        }

        return $priceListCollection;
    }

    public function getPriceListsByWebsite(Website $website)
    {
        $priceListsToWebsite = $this->registry
            ->getRepository('OroB2BPricingBundle:CombinedPriceListToWebsite')
            ->getPriceLists($website);
        $priceListCollection = [];
        foreach ($priceListsToWebsite as $priceListToWebsite) {
            $priceListCollection[] = new PriceListSequenceMember(
                $priceListToWebsite->getPriceList(),
                $priceListToWebsite->isMergeAllowed()
            );
        }
//        $this->registry->getRepository('')
    }

    /**
     * @param string $className
     * @return ObjectRepository
     */
    public function getRepository($className)
    {
        return $this->registry
            ->getManagerForClass($className)
            ->getRepository($className);
    }
}
