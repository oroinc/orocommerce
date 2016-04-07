<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Extension;

use Symfony\Component\Form\PreloadedExtension;

use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type\PriceListCollectionTypeExtensionsProvider;
use OroB2B\Bundle\PricingBundle\Form\Type\PriceListSelectWithPriorityType;
use OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\PriceListSelectTypeStub;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\EventListener\WebsiteListener;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToWebsite;
use OroB2B\Bundle\PricingBundle\Form\Extension\WebsiteFormExtension;
use OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Extension\Stub\WebsiteTypeStub;
use OroB2B\Bundle\WebsiteBundle\Form\Type\WebsiteType;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class WebsiteFormExtensionTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /**
     * @return array
     */
    protected function getExtensions()
    {
        /** @var WebsiteListener $listener */
        $listener = $this->getMockBuilder('OroB2B\Bundle\PricingBundle\EventListener\WebsiteListener')
            ->disableOriginalConstructor()
            ->getMock();

        $provider = new PriceListCollectionTypeExtensionsProvider();

        $extensions = [
            new PreloadedExtension(
                [
                    WebsiteType::NAME => new WebsiteTypeStub()
                ],
                [
                    WebsiteType::NAME => [
                        new WebsiteFormExtension('OroB2B\Bundle\PricingBundle\Entity\PriceListToWebsite', $listener)
                    ]
                ]
            )
        ];

        return array_merge($provider->getExtensions(), $extensions);
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param array $submitted
     * @param array $expected
     */
    public function testSubmit(array $submitted, array $expected)
    {
        $form = $this->factory->create(WebsiteType::NAME, [], []);
        $form->submit($submitted);
        $this->assertTrue($form->isValid());
        $this->assertEquals(
            $expected[WebsiteFormExtension::PRICE_LISTS_FALLBACK_FIELD],
            $form->get(WebsiteFormExtension::PRICE_LISTS_FALLBACK_FIELD)->getData()
        );
        $this->assertEquals(
            $expected[WebsiteFormExtension::PRICE_LISTS_TO_WEBSITE_FIELD],
            $form->get(WebsiteFormExtension::PRICE_LISTS_TO_WEBSITE_FIELD)->getData()
        );
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            [
                'submitted' => [
                    WebsiteFormExtension::PRICE_LISTS_FALLBACK_FIELD => '0',
                    WebsiteFormExtension::PRICE_LISTS_TO_WEBSITE_FIELD => [
                        0 => [
                            PriceListSelectWithPriorityType::PRICE_LIST_FIELD
                            => (string)PriceListSelectTypeStub::PRICE_LIST_1,
                            PriceListSelectWithPriorityType::PRIORITY_FIELD => '200',
                            PriceListSelectWithPriorityType::MERGE_ALLOWED_FIELD => true,
                        ],
                        1 => [
                            PriceListSelectWithPriorityType::PRICE_LIST_FIELD
                            => (string)PriceListSelectTypeStub::PRICE_LIST_2,
                            PriceListSelectWithPriorityType::PRIORITY_FIELD => '100',
                            PriceListSelectWithPriorityType::MERGE_ALLOWED_FIELD => false,
                        ]
                    ],
                ],
                'expected' => [
                    WebsiteFormExtension::PRICE_LISTS_FALLBACK_FIELD => 0,
                    WebsiteFormExtension::PRICE_LISTS_TO_WEBSITE_FIELD => [
                        0 => (new PriceListToWebsite())
                            ->setPriceList($this->getPriceList(PriceListSelectTypeStub::PRICE_LIST_1))
                            ->setPriority(200)
                            ->setMergeAllowed(true),
                        1 => (new PriceListToWebsite())
                            ->setPriceList($this->getPriceList(PriceListSelectTypeStub::PRICE_LIST_2))
                            ->setPriority(100)
                            ->setMergeAllowed(false)
                    ],
                ],
            ]
        ];
    }

    /**
     * @param int $id
     * @return PriceList
     */
    protected function getPriceList($id)
    {
        return $this->getEntity('OroB2B\Bundle\PricingBundle\Entity\PriceList', ['id' => $id]);
    }
}
