<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Form\Extension;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\PricingBundle\Tests\Unit\Form\Extension\Stub\AccountGroupTypeStub;
use Oro\Bundle\PricingBundle\Form\Type\PriceListsSettingsType;
use Oro\Bundle\PricingBundle\Tests\Unit\Form\Type\PriceListCollectionTypeExtensionsProvider;
use Oro\Bundle\WebsiteBundle\Form\Type\WebsiteScopedDataType;
use Oro\Bundle\PricingBundle\EventListener\AbstractPriceListCollectionAwareListener;
use Oro\Bundle\PricingBundle\Form\Type\PriceListSelectWithPriorityType;
use Oro\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\PriceListSelectTypeStub;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\AccountBundle\Form\Type\AccountGroupType;
use Oro\Bundle\PricingBundle\Entity\PriceListToAccountGroup;
use Oro\Bundle\PricingBundle\EventListener\AccountGroupListener;
use Oro\Bundle\PricingBundle\Form\Extension\AccountGroupFormExtension;

class AccountGroupFormExtensionTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /**
     * @return array
     */
    protected function getExtensions()
    {
        /** @var AccountGroupListener $listener */
        $listener = $this->getMockBuilder('Oro\Bundle\PricingBundle\EventListener\AccountGroupListener')
            ->disableOriginalConstructor()
            ->getMock();

        $provider = new PriceListCollectionTypeExtensionsProvider();
        $websiteScopedDataType = (new WebsiteScopedTypeMockProvider())->getWebsiteScopedDataType();

        $extensions = [
            new PreloadedExtension(
                [
                    PriceListsSettingsType::NAME => new PriceListsSettingsType(),
                    WebsiteScopedDataType::NAME => $websiteScopedDataType,
                    AccountGroupType::NAME => new AccountGroupTypeStub()
                ],
                [
                    AccountGroupType::NAME => [new AccountGroupFormExtension($listener)]
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
        $form = $this->factory->create(AccountGroupType::NAME, [], []);
        $form->submit([AbstractPriceListCollectionAwareListener::PRICE_LISTS_COLLECTION_FORM_FIELD_NAME => $submitted]);
        $data = $form->get(AccountGroupListener::PRICE_LISTS_COLLECTION_FORM_FIELD_NAME)->getData();
        $this->assertTrue($form->isValid());
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
                ],
                'expected' => [
                    1 => [
                        PriceListsSettingsType::FALLBACK_FIELD => 0,
                        PriceListsSettingsType::PRICE_LIST_COLLECTION_FIELD =>
                            [
                                0 => (new PriceListToAccountGroup())
                                    ->setPriceList($this->getPriceList(PriceListSelectTypeStub::PRICE_LIST_1))
                                    ->setPriority(200)
                                    ->setMergeAllowed(true),
                                1 => (new PriceListToAccountGroup())
                                    ->setPriceList($this->getPriceList(PriceListSelectTypeStub::PRICE_LIST_2))
                                    ->setPriority(100)
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
        return $this->getEntity('Oro\Bundle\PricingBundle\Entity\PriceList', [
            'id' => $id
        ]);
    }
}
