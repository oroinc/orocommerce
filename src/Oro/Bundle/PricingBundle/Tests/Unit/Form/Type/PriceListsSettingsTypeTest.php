<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Extension\SortableExtension;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToWebsite;
use Oro\Bundle\PricingBundle\Entity\PriceListWebsiteFallback;
use Oro\Bundle\PricingBundle\Form\Extension\PriceListFormExtension;
use Oro\Bundle\PricingBundle\Form\Type\PriceListSelectWithPriorityType;
use Oro\Bundle\PricingBundle\Form\Type\PriceListsSettingsType;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;

class PriceListsSettingsTypeTest extends FormIntegrationTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return (new PriceListCollectionTypeExtensionsProvider())->getExtensions();
    }

    private function getPriceList(int $id): PriceList
    {
        $priceList = new PriceList();
        ReflectionUtil::setId($priceList, $id);

        return $priceList;
    }

    public function testSubmit()
    {
        $pl1 = $this->getPriceList(1);
        $pl2 = $this->getPriceList(2);

        $form = $this->factory->create(
            PriceListsSettingsType::class,
            [
                PriceListsSettingsType::FALLBACK_FIELD => PriceListWebsiteFallback::CURRENT_WEBSITE_ONLY,
                PriceListsSettingsType::PRICE_LIST_COLLECTION_FIELD => [
                    (new PriceListToWebsite())->setSortOrder(100)->setPriceList($pl1)->setMergeAllowed(true),
                    (new PriceListToWebsite())->setSortOrder(200)->setPriceList($pl2)->setMergeAllowed(false),
                ]
            ],
            [
                PriceListsSettingsType::PRICE_LIST_RELATION_CLASS
                    => PriceListToWebsite::class,
                PriceListsSettingsType::FALLBACK_CHOICES => [
                    'oro.pricing.fallback.config.label' =>
                        PriceListWebsiteFallback::CONFIG,
                    'oro.pricing.fallback.current_website_only.label' =>
                        PriceListWebsiteFallback::CURRENT_WEBSITE_ONLY,
                ]
            ]
        );

        $form->submit([
            PriceListsSettingsType::FALLBACK_FIELD => PriceListWebsiteFallback::CURRENT_WEBSITE_ONLY,
            PriceListsSettingsType::PRICE_LIST_COLLECTION_FIELD => [
                [
                    PriceListSelectWithPriorityType::PRICE_LIST_FIELD => '2',
                    SortableExtension::POSITION_FIELD_NAME => '300',
                    PriceListFormExtension::MERGE_ALLOWED_FIELD => false
                ],
                [
                    PriceListSelectWithPriorityType::PRICE_LIST_FIELD => '1',
                    SortableExtension::POSITION_FIELD_NAME => '400',
                    PriceListFormExtension::MERGE_ALLOWED_FIELD => true
                ],
            ]
        ]);

        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals([
            PriceListsSettingsType::FALLBACK_FIELD => PriceListWebsiteFallback::CURRENT_WEBSITE_ONLY,
            PriceListsSettingsType::PRICE_LIST_COLLECTION_FIELD => [
                (new PriceListToWebsite())->setSortOrder(400)->setPriceList($pl1)->setMergeAllowed(true),
                (new PriceListToWebsite())->setSortOrder(300)->setPriceList($pl2)->setMergeAllowed(false),
            ]
        ], $form->getData());
    }
}
