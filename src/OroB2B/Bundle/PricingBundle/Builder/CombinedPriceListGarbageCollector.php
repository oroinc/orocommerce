<?php

namespace OroB2B\Bundle\PricingBundle\Builder;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\PricingBundle\DependencyInjection\Configuration;
use OroB2B\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository;

class CombinedPriceListGarbageCollector
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var string
     */
    protected $combinedPriceListClass;

    /**
     * @var CombinedPriceListRepository
     */
    protected $combinedPriceListsRepository;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @param ManagerRegistry $registry
     * @param ConfigManager $configManager
     */
    public function __construct(ManagerRegistry $registry, ConfigManager $configManager)
    {
        $this->registry = $registry;
        $this->configManager = $configManager;
    }

    public function cleanCombinedPriceLists()
    {
        $configCombinedPriceList = $this->configManager->get(Configuration::getConfigKeyToPriceList());
        $manager = $this->registry->getManager();
        $manager->getRepository('OroB2BPricingBundle:CombinedPriceListToAccount')->deleteInvalidRelations();
        $manager->getRepository('OroB2BPricingBundle:CombinedPriceListToAccountGroup')->deleteInvalidRelations();
        $manager->getRepository('OroB2BPricingBundle:CombinedPriceListToWebsite')->deleteInvalidRelations();
        $exceptPriceLists = [];
        if ($configCombinedPriceList) {
            $exceptPriceLists[] = $configCombinedPriceList;
        }
        $this->getCombinedPriceListsRepository()->deleteUnusedPriceLists($exceptPriceLists);
    }

    /**
     * @param string $combinedPriceListClass
     */
    public function setCombinedPriceListClass($combinedPriceListClass)
    {
        $this->combinedPriceListClass = $combinedPriceListClass;
        $this->combinedPriceListsRepository = null;
    }

    /**
     * @return CombinedPriceListRepository
     */
    protected function getCombinedPriceListsRepository()
    {
        if (!$this->combinedPriceListsRepository) {
            $this->combinedPriceListsRepository = $this->registry->getManagerForClass($this->combinedPriceListClass)
                ->getRepository($this->combinedPriceListClass);
        }

        return $this->combinedPriceListsRepository;
    }

    /**
     * @return CombinedPriceListRepository
     */
    protected function getCombinedPriceListRelationRepository()
    {
        if (!$this->combinedPriceListsRepository) {
            $this->combinedPriceListsRepository = $this->registry->getManagerForClass($this->combinedPriceListClass)
                ->getRepository($this->combinedPriceListClass);
        }

        return $this->combinedPriceListsRepository;
    }
}
