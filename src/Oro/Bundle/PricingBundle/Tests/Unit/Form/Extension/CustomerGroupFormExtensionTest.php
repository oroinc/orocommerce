<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Form\Type\CustomerGroupType;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\FormBundle\Form\Extension\SortableExtension;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomer;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomerGroup;
use Oro\Bundle\PricingBundle\EventListener\AbstractPriceListCollectionAwareListener;
use Oro\Bundle\PricingBundle\EventListener\CustomerGroupListener;
use Oro\Bundle\PricingBundle\Form\Extension\CustomerGroupFormExtension;
use Oro\Bundle\PricingBundle\Form\Extension\PriceListFormExtension;
use Oro\Bundle\PricingBundle\Form\Type\PriceListSelectWithPriorityType;
use Oro\Bundle\PricingBundle\Form\Type\PriceListsSettingsType;
use Oro\Bundle\PricingBundle\PricingStrategy\MergePricesCombiningStrategy;
use Oro\Bundle\PricingBundle\Tests\Unit\Form\Extension\Stub\CustomerGroupTypeStub;
use Oro\Bundle\PricingBundle\Tests\Unit\Form\Type\PriceListCollectionTypeExtensionsProvider;
use Oro\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\PriceListSelectTypeStub;
use Oro\Bundle\WebsiteBundle\Form\Type\WebsiteScopedDataType;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class CustomerGroupFormExtensionTest extends FormIntegrationTestCase
{
    private function getPriceList(int $id): PriceList
    {
        $priceList = new PriceList();
        ReflectionUtil::setId($priceList, $id);

        return $priceList;
    }

    public function testGetExtendedTypes(): void
    {
        $this->assertSame([CustomerGroupType::class], CustomerGroupFormExtension::getExtendedTypes());
    }

    public function testSetRelationClass()
    {
        $listener = $this->createMock(CustomerGroupListener::class);

        $customerFormExtension = new CustomerGroupFormExtension($listener);
        $customerFormExtension->setRelationClass(PriceListToCustomer::class);

        $this->assertEquals(
            PriceListToCustomer::class,
            ReflectionUtil::getPropertyValue($customerFormExtension, 'relationClass')
        );
    }

    public function testBuildFormFeatureDisabled()
    {
        $featureChecker = $this->createMock(FeatureChecker::class);
        $featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('feature1', null)
            ->willReturn(false);

        $listener = $this->createMock(CustomerGroupListener::class);
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->never())
            ->method('add');

        $customerFormExtension = new CustomerGroupFormExtension($listener);
        $customerFormExtension->setFeatureChecker($featureChecker);
        $customerFormExtension->addFeature('feature1');
        $customerFormExtension->buildForm($builder, []);
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        $featureChecker = $this->createMock(FeatureChecker::class);
        $featureChecker->expects($this->any())
            ->method('isFeatureEnabled')
            ->with('feature1', null)
            ->willReturn(true);

        $listener = $this->createMock(CustomerGroupListener::class);

        $customerGroupFormExtension = new CustomerGroupFormExtension($listener);
        $customerGroupFormExtension->setFeatureChecker($featureChecker);
        $customerGroupFormExtension->addFeature('feature1');

        $provider = new PriceListCollectionTypeExtensionsProvider();
        $websiteScopedDataType = (new WebsiteScopedTypeMockProvider())->getWebsiteScopedDataType();

        $configManager = $this->createMock(ConfigManager::class);
        $configManager->expects($this->any())
            ->method('get')
            ->with('oro_pricing.price_strategy')
            ->willReturn(MergePricesCombiningStrategy::NAME);

        $extensions = [
            new PreloadedExtension(
                [
                    PriceListsSettingsType::class => new PriceListsSettingsType(),
                    WebsiteScopedDataType::class => $websiteScopedDataType,
                    CustomerGroupType::class => new CustomerGroupTypeStub()
                ],
                [
                    CustomerGroupTypeStub::class => [$customerGroupFormExtension],
                    FormType::class => [new SortableExtension()],
                    PriceListSelectWithPriorityType::class => [new PriceListFormExtension($configManager)]
                ]
            )
        ];

        return array_merge($provider->getExtensions(), $extensions);
    }

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmit(array $submitted, array $expected)
    {
        $form = $this->factory->create(CustomerGroupType::class, [], []);
        $form->submit([AbstractPriceListCollectionAwareListener::PRICE_LISTS_COLLECTION_FORM_FIELD_NAME => $submitted]);
        $data = $form->get(CustomerGroupListener::PRICE_LISTS_COLLECTION_FORM_FIELD_NAME)->getData();
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expected, $data);
    }

    public function submitDataProvider(): array
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
}
