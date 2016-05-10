<?php
namespace OroB2B\Bundle\PricingBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectRepository;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\Entity\BasePriceListRelation;
use OroB2B\Bundle\PricingBundle\Entity\PriceListAccountFallback;
use OroB2B\Bundle\PricingBundle\Entity\PriceListAccountGroupFallback;
use OroB2B\Bundle\PricingBundle\Entity\PriceListWebsiteFallback;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListToAccountGroupRepository;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListToAccountRepository;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListToWebsiteRepository;
use OroB2B\Bundle\PricingBundle\SystemConfig\PriceListConfig;
use OroB2B\Bundle\PricingBundle\SystemConfig\PriceListConfigConverter;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class PriceListCollectionProvider
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var PriceListConfigConverter
     */
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
        $activeRelations = [];
        foreach ($priceListsConfig as $priceList) {
            if ($priceList->getPriceList()->isActive()) {
                $activeRelations[] = $priceList;
            }
        }
        return $this->getPriceListSequenceMembers($activeRelations);
    }

    /**
     * @param Website $website
     * @return PriceListSequenceMember[]
     */
    public function getPriceListsByWebsite(Website $website)
    {
        /** @var PriceListToWebsiteRepository $repo */
        $repo = $this->getRepository('OroB2BPricingBundle:PriceListToWebsite');
        $priceListCollection = $this->getPriceListSequenceMembers(
            $repo->getPriceLists($website)
        );
        $fallbackEntity = $this->registry
            ->getRepository('OroB2BPricingBundle:PriceListWebsiteFallback')
            ->findOneBy(['website' => $website]);
        if (!$fallbackEntity || $fallbackEntity->getFallback() === PriceListWebsiteFallback::CONFIG) {
            return array_merge($priceListCollection, $this->getPriceListsByConfig());
        }
        return $priceListCollection;
    }

    /**
     * @param AccountGroup $accountGroup
     * @param Website $website
     * @return PriceListSequenceMember[]
     */
    public function getPriceListsByAccountGroup(AccountGroup $accountGroup, Website $website)
    {
        /** @var PriceListToAccountGroupRepository $repo */
        $repo = $this->getRepository('OroB2BPricingBundle:PriceListToAccountGroup');
        $priceListCollection = $this->getPriceListSequenceMembers(
            $repo->getPriceLists($accountGroup, $website)
        );
        $fallbackEntity = $this->registry
            ->getRepository('OroB2BPricingBundle:PriceListAccountGroupFallback')
            ->findOneBy(['accountGroup' => $accountGroup, 'website' => $website]);
        if (!$fallbackEntity || $fallbackEntity->getFallback() === PriceListAccountGroupFallback::WEBSITE) {
            return array_merge($priceListCollection, $this->getPriceListsByWebsite($website));
        }
        return $priceListCollection;
    }

    /**
     * @param Account $account
     * @param Website $website
     * @return PriceListSequenceMember[]
     */
    public function getPriceListsByAccount(Account $account, Website $website)
    {
        /** @var PriceListToAccountRepository $repo */
        $repo = $this->getRepository('OroB2BPricingBundle:PriceListToAccount');
        $priceListCollection = $this->getPriceListSequenceMembers(
            $repo->getPriceLists($account, $website)
        );
        if ($account->getGroup()) {
            $fallbackEntity = $this->registry
                ->getRepository('OroB2BPricingBundle:PriceListAccountFallback')
                ->findOneBy(['account' => $account, 'website' => $website]);
            if (!$fallbackEntity || $fallbackEntity->getFallback() === PriceListAccountFallback::ACCOUNT_GROUP) {
                return array_merge(
                    $priceListCollection,
                    $this->getPriceListsByAccountGroup($account->getGroup(), $website)
                );
            }
        } else {
            return array_merge($priceListCollection, $this->getPriceListsByWebsite($website));
        }
        return $priceListCollection;
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

    /**
     * @param BasePriceListRelation[]|PriceListConfig[] $priceListsRelations
     * @return PriceListSequenceMember[]
     */
    protected function getPriceListSequenceMembers($priceListsRelations)
    {
        $priceListCollection = [];
        foreach ($priceListsRelations as $priceListsRelation) {
            $priceListCollection[] = new PriceListSequenceMember(
                $priceListsRelation->getPriceList(),
                $priceListsRelation->isMergeAllowed()
            );
        }
        return $priceListCollection;
    }
}
