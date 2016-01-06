<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\Entity\PriceListAccountFallback;
use OroB2B\Bundle\PricingBundle\Entity\PriceListAccountGroupFallback;
use OroB2B\Bundle\PricingBundle\Entity\PriceListWebsiteFallback;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class LoadLevelFallbacks extends AbstractFixture implements DependentFixtureInterface
{
    protected $fallbacks = [
        'account' => [
            'account.level_1.1.1' => false,
            'account.level_1.1' => true,
            'account.level_1.2' => true,
            'account.orphan' => false,
        ],
        'accountGroup' => [
            'account_group.group1' => true,
            'account_group.group2' => false,
            'account_group.group3' => false,
        ],
        'website' => [
            'US' => true,
            'Canada' => false,
        ],
    ];

    /**
     * @inheritDoc
     */
    public function getDependencies()
    {
        return [
            'OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData',
            'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccounts',
            'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadGroups',
        ];
    }

    /**
     * @inheritDoc
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->fallbacks['account'] as $accountReference => $fallbackValue) {
            $priceListAccountFallback = new PriceListAccountFallback();
            /** @var Account $account */
            $account = $this->getReference($accountReference);
            $priceListAccountFallback->setAccount($account);
            $priceListAccountFallback->setFallback($fallbackValue);
            $manager->persist($priceListAccountFallback);
        }

        foreach ($this->fallbacks['accountGroup'] as $accountGroupReference => $fallbackValue) {
            $priceListAccountGroupFallback = new PriceListAccountGroupFallback();
            /** @var AccountGroup $accountGroup */
            $accountGroup = $this->getReference($accountGroupReference);
            $priceListAccountGroupFallback->setAccountGroup($accountGroup);
            $priceListAccountGroupFallback->setFallback($fallbackValue);
            $manager->persist($priceListAccountGroupFallback);
        }

        foreach ($this->fallbacks['website'] as $websiteReference => $fallbackValue) {
            $priceListWebsiteFallback = new PriceListWebsiteFallback();
            /** @var Website $website */
            $website = $this->getReference($websiteReference);
            $priceListWebsiteFallback->setWebsite($website);
            $priceListWebsiteFallback->setFallback($fallbackValue);
            $manager->persist($priceListWebsiteFallback);
        }
        $manager->flush();
    }
}
