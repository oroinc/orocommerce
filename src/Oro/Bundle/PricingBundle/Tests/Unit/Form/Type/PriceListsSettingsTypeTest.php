<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Extension\SortableExtension;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToWebsite;
use Oro\Bundle\PricingBundle\Entity\PriceListWebsiteFallback;
use Oro\Bundle\PricingBundle\Form\Extension\PriceListFormExtension;
use Oro\Bundle\PricingBundle\Form\Type\PriceListSelectWithPriorityType;
use Oro\Bundle\PricingBundle\Form\Type\PriceListsSettingsType;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;

class PriceListsSettingsTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $provider = new PriceListCollectionTypeExtensionsProvider();

        return $provider->getExtensions();
    }

    public function testSubmit()
    {
        /** @var PriceList $pl1 */
        $pl1 = $this->getEntity('Oro\Bundle\PricingBundle\Entity\PriceList', ['id' => 1]);

        /** @var PriceList $pl2 */
        $pl2 = $this->getEntity('Oro\Bundle\PricingBundle\Entity\PriceList', ['id' => 2]);

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
                    => 'Oro\Bundle\PricingBundle\Entity\PriceListToWebsite',
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
