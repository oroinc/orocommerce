<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;

use OroB2B\Bundle\PricingBundle\Form\Type\PriceListsSettingsType;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToWebsite;
use OroB2B\Bundle\PricingBundle\Entity\PriceListWebsiteFallback;
use OroB2B\Bundle\PricingBundle\Form\Type\PriceListSelectWithPriorityType;

class PriceListsSettingsTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /**
     * @var PriceListsSettingsType|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $priceListsSettingsType;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->priceListsSettingsType = new PriceListsSettingsType();

        parent::setUp();
    }

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
        $pl1 = $this->getEntity('OroB2B\Bundle\PricingBundle\Entity\PriceList', ['id' => 1]);

        /** @var PriceList $pl2 */
        $pl2 = $this->getEntity('OroB2B\Bundle\PricingBundle\Entity\PriceList', ['id' => 2]);

        $form = $this->factory->create(
            $this->priceListsSettingsType,
            [
                PriceListsSettingsType::FALLBACK_FIELD => PriceListWebsiteFallback::CURRENT_WEBSITE_ONLY,
                PriceListsSettingsType::PRICE_LIST_COLLECTION_FIELD => [
                    (new PriceListToWebsite())->setPriority(100)->setPriceList($pl1)->setMergeAllowed(true),
                    (new PriceListToWebsite())->setPriority(200)->setPriceList($pl2)->setMergeAllowed(false),
                ]
            ],
            [
                PriceListsSettingsType::PRICE_LIST_RELATION_CLASS
                    => 'OroB2B\Bundle\PricingBundle\Entity\PriceListToWebsite',
                PriceListsSettingsType::FALLBACK_CHOICES => [
                    PriceListWebsiteFallback::CONFIG =>
                        'orob2b.pricing.fallback.config.label',
                    PriceListWebsiteFallback::CURRENT_WEBSITE_ONLY =>
                        'orob2b.pricing.fallback.current_website_only.label',
                ]
            ]
        );

        $form->submit([
            PriceListsSettingsType::FALLBACK_FIELD => PriceListWebsiteFallback::CURRENT_WEBSITE_ONLY,
            PriceListsSettingsType::PRICE_LIST_COLLECTION_FIELD => [
                [
                    PriceListSelectWithPriorityType::PRICE_LIST_FIELD => '2',
                    PriceListSelectWithPriorityType::PRIORITY_FIELD => '300',
                    PriceListSelectWithPriorityType::MERGE_ALLOWED_FIELD => false
                ],
                [
                    PriceListSelectWithPriorityType::PRICE_LIST_FIELD => '1',
                    PriceListSelectWithPriorityType::PRIORITY_FIELD => '400',
                    PriceListSelectWithPriorityType::MERGE_ALLOWED_FIELD => true
                ],
            ]
        ]);

        $this->assertTrue($form->isValid());
        $this->assertEquals([
            PriceListsSettingsType::FALLBACK_FIELD => PriceListWebsiteFallback::CURRENT_WEBSITE_ONLY,
            PriceListsSettingsType::PRICE_LIST_COLLECTION_FIELD => [
                (new PriceListToWebsite())->setPriority(400)->setPriceList($pl1)->setMergeAllowed(true),
                (new PriceListToWebsite())->setPriority(300)->setPriceList($pl2)->setMergeAllowed(false),
            ]
        ], $form->getData());
    }
}
