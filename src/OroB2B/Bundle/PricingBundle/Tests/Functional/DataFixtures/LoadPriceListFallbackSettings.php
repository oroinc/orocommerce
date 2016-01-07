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

class LoadPriceListFallbackSettings extends AbstractFixture implements DependentFixtureInterface
{
    protected $fallbackSettings = [
        'account' => [
            'account.level_1_1' => PriceListAccountFallback::ACCOUNT_GROUP,
            'account.level_1.3' => PriceListAccountFallback::ACCOUNT_GROUP,
            'account.level_1.2' => PriceListAccountFallback::CURRENT_ACCOUNT_ONLY,
        ],
        'accountGroup' => [
            'account_group.group1' => PriceListAccountGroupFallback::WEBSITE,
            'account_group.group2' => PriceListAccountGroupFallback::CURRENT_ACCOUNT_GROUP_ONLY,
        ],
        'website' => [
            'US' => PriceListWebsiteFallback::CONFIG,
            'Canada' => PriceListWebsiteFallback::CURRENT_WEBSITE_ONLY,
        ],
    ];

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->fallbackSettings['account'] as $accountReference => $fallbackValue) {
            $priceListAccountFallback = new PriceListAccountFallback();
            /** @var Account $account */
            $account = $this->getReference($accountReference);
            $priceListAccountFallback->setAccount($account);
            $priceListAccountFallback->setFallback($fallbackValue);
            $manager->persist($priceListAccountFallback);
        }

        foreach ($this->fallbackSettings['accountGroup'] as $accountGroupReference => $fallbackValue) {
            $priceListAccountGroupFallback = new PriceListAccountGroupFallback();
            /** @var AccountGroup $accountGroup */
            $accountGroup = $this->getReference($accountGroupReference);
            $priceListAccountGroupFallback->setAccountGroup($accountGroup);
            $priceListAccountGroupFallback->setFallback($fallbackValue);
            $manager->persist($priceListAccountGroupFallback);
        }

        foreach ($this->fallbackSettings['website'] as $websiteReference => $fallbackValue) {
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
