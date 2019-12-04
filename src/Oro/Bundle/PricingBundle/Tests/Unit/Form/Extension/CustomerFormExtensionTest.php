<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Form\Type\CustomerType;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\FormBundle\Form\Extension\SortableExtension;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomer;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomerGroup;
use Oro\Bundle\PricingBundle\EventListener\AbstractPriceListCollectionAwareListener;
use Oro\Bundle\PricingBundle\EventListener\CustomerListener;
use Oro\Bundle\PricingBundle\Form\Extension\CustomerFormExtension;
use Oro\Bundle\PricingBundle\Form\Extension\PriceListFormExtension;
use Oro\Bundle\PricingBundle\Form\Type\PriceListSelectWithPriorityType;
use Oro\Bundle\PricingBundle\Form\Type\PriceListsSettingsType;
use Oro\Bundle\PricingBundle\PricingStrategy\MergePricesCombiningStrategy;
use Oro\Bundle\PricingBundle\Tests\Unit\Form\Extension\Stub\CustomerTypeStub;
use Oro\Bundle\PricingBundle\Tests\Unit\Form\Type\PriceListCollectionTypeExtensionsProvider;
use Oro\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\PriceListSelectTypeStub;
use Oro\Bundle\WebsiteBundle\Form\Type\WebsiteScopedDataType;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;

class CustomerFormExtensionTest extends FormIntegrationTestCase
{
    use EntityTrait;

    public function testGetExtendedTypes()
    {
        $this->assertSame([CustomerType::class], CustomerFormExtension::getExtendedTypes());
    }

    public function testSetRelationClass()
    {
        /** @var CustomerListener|\PHPUnit\Framework\MockObject\MockObject $listener */
        $listener = $this->createMock(CustomerListener::class);

        $customerFormExtension = new CustomerFormExtension($listener);
        $customerFormExtension->setRelationClass(PriceListToCustomerGroup::class);

        $reflection = new \ReflectionObject($customerFormExtension);
        $relationClass = $reflection->getProperty('relationClass');
        $relationClass->setAccessible(true);

        $this->assertSame(PriceListToCustomerGroup::class, $relationClass->getValue($customerFormExtension));
    }

    public function testBuildFormFeatureDisabled()
    {
        /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject $featureChecker */
        $featureChecker = $this->createMock(FeatureChecker::class);
        $featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('feature1', null)
            ->willReturn(false);

        /** @var CustomerListener|\PHPUnit\Framework\MockObject\MockObject $listener */
        $listener = $this->createMock(CustomerListener::class);
        /** @var FormBuilderInterface|\PHPUnit\Framework\MockObject\MockObject $builder */
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->never())->method('add');

        $customerFormExtension = new CustomerFormExtension($listener);
        $customerFormExtension->setFeatureChecker($featureChecker);
        $customerFormExtension->addFeature('feature1');
        $customerFormExtension->buildForm($builder, []);
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject $featureChecker */
        $featureChecker = $this->createMock(FeatureChecker::class);
        $featureChecker->expects($this->any())
            ->method('isFeatureEnabled')
            ->with('feature1', null)
            ->willReturn(true);

        /** @var CustomerListener $listener */
        $listener = $this->getMockBuilder('Oro\Bundle\PricingBundle\EventListener\CustomerListener')
            ->disableOriginalConstructor()
            ->getMock();

        $customerFormExtension = new CustomerFormExtension($listener);
        $customerFormExtension->setFeatureChecker($featureChecker);
        $customerFormExtension->addFeature('feature1');

        $provider = new PriceListCollectionTypeExtensionsProvider();
        $websiteScopedDataType = (new WebsiteScopedTypeMockProvider())->getWebsiteScopedDataType();

        $configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $configManager->expects($this->any())
            ->method('get')
            ->with('oro_pricing.price_strategy')
            ->willReturn(MergePricesCombiningStrategy::NAME);

        $extensions = [
            new PreloadedExtension(
                [
                    PriceListsSettingsType::class => new PriceListsSettingsType(),
                    WebsiteScopedDataType::class => $websiteScopedDataType,
                    CustomerType::class => new CustomerTypeStub()
                ],
                [
                    CustomerTypeStub::class => [$customerFormExtension],
                    FormType::class => [new SortableExtension()],
                    PriceListSelectWithPriorityType::class => [new PriceListFormExtension($configManager)]

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
        $form = $this->factory->create(CustomerType::class, [], []);
        $form->submit([AbstractPriceListCollectionAwareListener::PRICE_LISTS_COLLECTION_FORM_FIELD_NAME => $submitted]);
        $data = $form->get(CustomerListener::PRICE_LISTS_COLLECTION_FORM_FIELD_NAME)->getData();
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
                                    PriceListFormExtension::MERGE_ALLOWED_FIELD => true,
                                ],
                                [
                                    PriceListSelectWithPriorityType::PRICE_LIST_FIELD
                                        => (string)PriceListSelectTypeStub::PRICE_LIST_2,
                                    SortableExtension::POSITION_FIELD_NAME => '100',
                                    PriceListFormExtension::MERGE_ALLOWED_FIELD => false,
                                ]
                            ],
                    ],
                ],
                'expected' => [
                    1 => [
                        PriceListsSettingsType::FALLBACK_FIELD => 0,
                        PriceListsSettingsType::PRICE_LIST_COLLECTION_FIELD =>
                            [
                                (new PriceListToCustomer())
                                    ->setPriceList($this->getPriceList(PriceListSelectTypeStub::PRICE_LIST_1))
                                    ->setSortOrder(200)
                                    ->setMergeAllowed(true),
                                (new PriceListToCustomer())
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
