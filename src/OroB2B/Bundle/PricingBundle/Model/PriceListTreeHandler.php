<?php

namespace OroB2B\Bundle\PricingBundle\Model;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\PricingBundle\DependencyInjection\OroB2BPricingExtension;
use OroB2B\Bundle\PricingBundle\Entity\BasePriceList;
use OroB2B\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
use OroB2B\Bundle\WebsiteBundle\Manager\WebsiteManager;
use OroB2B\Bundle\PricingBundle\DependencyInjection\Configuration;

class PriceListTreeHandler
{
    /** @var ManagerRegistry */
    protected $registry;

    /**  @var WebsiteManager */
    protected $websiteManager;

    /**  @var string */
    protected $priceListClass;

    /**  @var PriceListRepository */
    protected $priceListRepository;

    /** @var ConfigManager */
    protected $configManager;

    /**
     * @param ManagerRegistry $registry
     * @param WebsiteManager $websiteManager
     * @param ConfigManager $configManager
     */
    public function __construct(
        ManagerRegistry $registry,
        WebsiteManager $websiteManager,
        ConfigManager $configManager
    ) {
        $this->registry = $registry;
        $this->websiteManager = $websiteManager;
        $this->configManager = $configManager;
    }

    /**
     * @param AccountUser|null $accountUser
     * @return BasePriceList
     */
    public function getPriceList(AccountUser $accountUser = null)
    {
        $website = $this->websiteManager->getCurrentWebsite();
        if ($accountUser) {
            $account = $accountUser->getAccount();

            if ($account) {
                $priceList = $this->getPriceListRepository()->getPriceListByAccount($account, $website);
                if ($priceList) {
                    return $priceList;
                }
                if ($account->getGroup()) {
                    $priceList = $this->getPriceListRepository()
                        ->getPriceListByAccountGroup($account->getGroup(), $website);
                    if ($priceList) {
                        return $priceList;
                    }
                }
            }
        }

        $priceList =  $priceList = $this->getPriceListRepository()->getPriceListByWebsite($website);
        if (!$priceList) {
            $priceList = $this->getPriceListFromConfig();
        }

        return $priceList;
    }

    /**
     * @return null|BasePriceList
     */
    protected function getPriceListFromConfig()
    {
        $key = implode(
            ConfigManager::SECTION_MODEL_SEPARATOR,
            [OroB2BPricingExtension::ALIAS, Configuration::COMBINED_PRICE_LIST]
        );
        $priceListId = $this->configManager->get($key);

        if (!$priceListId) {
            return null;
        }

        return $this->getPriceListRepository()->find($priceListId);
    }

    /**
     * @return CombinedPriceListRepository
     */
    protected function getPriceListRepository()
    {
        if (!$this->priceListRepository) {
            $this->priceListRepository = $this->registry->getManagerForClass($this->priceListClass)
                ->getRepository($this->priceListClass);
        }

        return $this->priceListRepository;
    }

    /**
     * @param string $priceListClass
     */
    public function setPriceListClass($priceListClass)
    {
        $this->priceListClass = $priceListClass;
        $this->priceListRepository = null;
    }
}
