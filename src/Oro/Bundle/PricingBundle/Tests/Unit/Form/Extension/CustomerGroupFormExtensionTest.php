<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Form\Extension;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\PricingBundle\Tests\Unit\Form\Extension\Stub\CustomerGroupTypeStub;
use Oro\Bundle\PricingBundle\Form\Type\PriceListsSettingsType;
use Oro\Bundle\PricingBundle\Tests\Unit\Form\Type\PriceListCollectionTypeExtensionsProvider;
use Oro\Bundle\WebsiteBundle\Form\Type\WebsiteScopedDataType;
use Oro\Bundle\PricingBundle\EventListener\AbstractPriceListCollectionAwareListener;
use Oro\Bundle\PricingBundle\Form\Type\PriceListSelectWithPriorityType;
use Oro\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\PriceListSelectTypeStub;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\CustomerBundle\Form\Type\CustomerGroupType;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomerGroup;
use Oro\Bundle\PricingBundle\EventListener\CustomerGroupListener;
use Oro\Bundle\PricingBundle\Form\Extension\CustomerGroupFormExtension;
use Oro\Bundle\FormBundle\Form\Extension\SortableExtension;

class CustomerGroupFormExtensionTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /**
     * @return array
     */
    protected function getExtensions()
    {
        /** @var CustomerGroupListener $listener */
        $listener = $this->getMockBuilder('Oro\Bundle\PricingBundle\EventListener\CustomerGroupListener')
            ->disableOriginalConstructor()
            ->getMock();

        $provider = new PriceListCollectionTypeExtensionsProvider();
        $websiteScopedDataType = (new WebsiteScopedTypeMockProvider())->getWebsiteScopedDataType();

        $extensions = [
            new PreloadedExtension(
                [
                    PriceListsSettingsType::NAME => new PriceListsSettingsType(),
                    WebsiteScopedDataType::NAME => $websiteScopedDataType,
                    CustomerGroupType::NAME => new CustomerGroupTypeStub()
                ],
                [
                    CustomerGroupType::NAME => [new CustomerGroupFormExtension($listener)],
                    ['form' => [new SortableExtension()]]
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
        $form = $this->factory->create(CustomerGroupType::NAME, [], []);
        $form->submit([AbstractPriceListCollectionAwareListener::PRICE_LISTS_COLLECTION_FORM_FIELD_NAME => $submitted]);
        $data = $form->get(CustomerGroupListener::PRICE_LISTS_COLLECTION_FORM_FIELD_NAME)->getData();
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expected, $data);
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            [
                'submitted' => [
                    1 => [
                        PriceListsSettingsType::FALLBACK_FIELD => '0',
                        PriceListsSettingsType::PRICE_LIST_COLLECTION_FIELD =>
                            [
                                [
                                    PriceListSelectWithPriorityType::PRICE_LIST_FIELD
                                        => (string)PriceListSelectTypeStub::PRICE_LIST_1,
                                    SortableExtension::POSITION_FIELD_NAME => '200',
                                    PriceListSelectWithPriorityType::MERGE_ALLOWED_FIELD => true,
                                ],
                                [
                                    PriceListSelectWithPriorityType::PRICE_LIST_FIELD
                                        => (string)PriceListSelectTypeStub::PRICE_LIST_2,
                                    SortableExtension::POSITION_FIELD_NAME => '100',
                                    PriceListSelectWithPriorityType::MERGE_ALLOWED_FIELD => false,
                                ]
                            ],
                    ],
                ],
                'expected' => [
                    1 => [
                        PriceListsSettingsType::FALLBACK_FIELD => 0,
                        PriceListsSettingsType::PRICE_LIST_COLLECTION_FIELD =>
                            [
                                (new PriceListToCustomerGroup())
                                    ->setPriceList($this->getPriceList(PriceListSelectTypeStub::PRICE_LIST_1))
                                    ->setSortOrder(200)
                                    ->setMergeAllowed(true),
                                (new PriceListToCustomerGroup())
                                    ->setPriceList($this->getPriceList(PriceListSelectTypeStub::PRICE_LIST_2))
                                    ->setSortOrder(100)
                                    ->setMergeAllowed(false)
                            ],
                    ],
                ]
            ]
        ];
    }

    /**
     * @param int $id
     * @return PriceList
     */
    protected function getPriceList($id)
    {
        return $this->getEntity(PriceList::class, [
            'id' => $id
        ]);
    }
}
