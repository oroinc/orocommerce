<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\PricingBundle\Entity\BasePriceListRelation;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToWebsite;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

class LoadDependentPriceListRelations extends AbstractFixture implements DependentFixtureInterface
{
    const DEPENDENT_PRICE_LIST_TO_WEBSITE_1 = 'price_list_6_CA';

    /**
     * @var array
     */
    protected $data = [
        'CA' => [
            'priceLists' => [
                [
                    'reference' => self::DEPENDENT_PRICE_LIST_TO_WEBSITE_1,
                    'priceList' => LoadDependentPriceLists::DEPENDENT_PRICE_LIST_1,
                    'sortOrder' => 1,
                    'mergeAllowed' => true,
                ],
            ],
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadWebsiteData::class,
            LoadDependentPriceLists::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->data as $websiteReference => $priceListsData) {
            /** @var Website $website */
            $website = $this->getReference($websiteReference);
            foreach ($priceListsData['priceLists'] as $priceListData) {
                $priceListToWebsite = new PriceListToWebsite();
                $this->fillRelationData($priceListToWebsite, $website, $priceListData);

                $manager->persist($priceListToWebsite);
                $this->setReference($priceListData['reference'], $priceListToWebsite);
            }
        }

        $manager->flush();
    }

    protected function fillRelationData(
        BasePriceListRelation $priceListToWebsite,
        Website $website,
        array $priceListData
    ) {
        /** @var PriceList $priceList */
        $priceList = $this->getReference($priceListData['priceList']);
        $priceListToWebsite->setSortOrder($priceListData['sortOrder']);
        $priceListToWebsite->setMergeAllowed($priceListData['mergeAllowed']);
        $priceListToWebsite->setWebsite($website);
        $priceListToWebsite->setPriceList($priceList);
    }
}
