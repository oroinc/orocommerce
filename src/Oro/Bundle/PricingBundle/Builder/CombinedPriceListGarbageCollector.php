<?php

namespace Oro\Bundle\PricingBundle\Builder;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\PricingBundle\DependencyInjection\Configuration;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository;

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
        $manager->getRepository('OroPricingBundle:CombinedPriceListToAccount')->deleteInvalidRelations();
        $manager->getRepository('OroPricingBundle:CombinedPriceListToAccountGroup')->deleteInvalidRelations();
        $manager->getRepository('OroPricingBundle:CombinedPriceListToWebsite')->deleteInvalidRelations();
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
