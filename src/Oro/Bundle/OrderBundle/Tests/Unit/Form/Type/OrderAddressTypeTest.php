<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\AddressBundle\Form\Type\AddressType as AddressFormType;
use Oro\Bundle\AddressBundle\Validator\Constraints\NameOrOrganization;
use Oro\Bundle\CustomerBundle\Entity\CustomerOwnerAwareInterface;
use Oro\Bundle\CustomerBundle\Tests\Unit\Form\Type\Stub\AddressTypeStub;
use Oro\Bundle\FormBundle\Tests\Unit\Stub\StripTagsExtensionStub;
use Oro\Bundle\ImportExportBundle\Serializer\Serializer;
use Oro\Bundle\LocaleBundle\Formatter\AddressFormatter;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Form\Type\OrderAddressSelectType;
use Oro\Bundle\OrderBundle\Form\Type\OrderAddressType;
use Oro\Bundle\OrderBundle\Manager\OrderAddressManager;
use Oro\Bundle\OrderBundle\Manager\TypedOrderAddressCollection;
use Oro\Bundle\OrderBundle\Provider\OrderAddressSecurityProvider;
use Oro\Bundle\TranslationBundle\Form\Type\TranslatableEntityType;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityTypeStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderAddressTypeTest extends FormIntegrationTestCase
{
    /** @var OrderAddressSecurityProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $orderAddressSecurityProvider;

    protected function setUp(): void
    {
        $this->orderAddressSecurityProvider = $this->createMock(OrderAddressSecurityProvider::class);
        $this->orderAddressSecurityProvider->expects($this->any())
            ->method('isManualEditGranted')
            ->willReturn(true);

        parent::setUp();
    }

    public function testGetBlockPrefix()
    {
        $type = new OrderAddressType($this->orderAddressSecurityProvider);
        $this->assertEquals('oro_order_address_type', $type->getBlockPrefix());
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setRequired')
            ->with(['object', 'addressType'])
            ->willReturnSelf();
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'data_class' => OrderAddress::class,
                'constraints' => [new NameOrOrganization()]
            ])
            ->willReturnSelf();
        $resolver->expects($this->once())
            ->method('setAllowedValues')
            ->with('addressType', ['billing', 'shipping'])
            ->willReturnSelf();
        $resolver->expects($this->once())
            ->method('setAllowedTypes')
            ->with('object', CustomerOwnerAwareInterface::class)
            ->willReturnSelf();

        $type = new OrderAddressType($this->orderAddressSecurityProvider);
        $type->configureOptions($resolver);
    }

    public function testBuildForm()
    {
        $form = $this->factory->create(OrderAddressType::class, null, [
            'object' => new Order(),
            'addressType' => 'billing'
        ]);

        $this->assertTrue($form->has('id'));
        $this->assertTrue($form->has('label'));
        $this->assertTrue($form->has('namePrefix'));
        $this->assertTrue($form->has('firstName'));
        $this->assertTrue($form->has('middleName'));
        $this->assertTrue($form->has('lastName'));
        $this->assertTrue($form->has('nameSuffix'));
        $this->assertTrue($form->has('organization'));
        $this->assertTrue($form->has('country'));
        $this->assertTrue($form->has('street'));
        $this->assertTrue($form->has('city'));
        $this->assertTrue($form->has('region'));
        $this->assertTrue($form->has('postalCode'));
        $this->assertTrue($form->has('customerAddress'));
        $this->assertTrue($form->has('phone'));
    }

    public function testGetParent()
    {
        $type = new OrderAddressType($this->orderAddressSecurityProvider);

        $this->assertIsString($type->getParent());
        $this->assertEquals(AddressFormType::class, $type->getParent());
    }

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmit(OrderAddress $defaultData, array $submittedData, OrderAddress $expectedData)
    {
        $form = $this->factory->create(OrderAddressType::class, $defaultData, [
            'object' => new Order(),
            'addressType' => 'billing'
        ]);

        $this->assertEquals($defaultData, $form->getData());
        $this->assertEquals($defaultData, $form->getViewData());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());

        $this->assertEquals($expectedData, $form->getData());
    }

    public function submitDataProvider(): array
    {
        return [
            'new order address' => [
                'defaultData' => $this->getOrderAddress(),
                'submittedData' => [
                    'label' => 'new address'
                ],
                'expectedData' => $this->getOrderAddress('new address'),
            ],
            'edit order address' => [
                'defaultData' => $this->getOrderAddress('existing address'),
                'submittedData' => [
                    'label' => 'new label',
                    'firstName' => 'First Name',
                    'lastName' => 'Last Name',
                    'phone' => '0123456789',
                    'street' => 'Street',
                ],
                'expectedData' => $this->getOrderAddress(
                    'new label',
                    'First Name',
                    'Last Name',
                    '0123456789_stripped',
                    'Street'
                ),
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions(): array
    {
        $addressManager = $this->createMock(OrderAddressManager::class);
        $addressManager->expects($this->any())
            ->method('getGroupedAddresses')
            ->willReturn(new TypedOrderAddressCollection(null, 'billing', []));

        return [
            new PreloadedExtension([
                new OrderAddressType($this->orderAddressSecurityProvider),
                AddressFormType::class => new AddressTypeStub(),
                TranslatableEntityType::class => new EntityTypeStub([
                    AddressType::TYPE_BILLING => new AddressType(AddressType::TYPE_BILLING),
                    AddressType::TYPE_SHIPPING => new AddressType(AddressType::TYPE_SHIPPING),
                ]),
                new OrderAddressSelectType(
                    $addressManager,
                    $this->createMock(AddressFormatter::class),
                    $this->orderAddressSecurityProvider,
                    $this->createMock(Serializer::class)
                )
            ], [
                FormType::class => [new StripTagsExtensionStub($this)]
            ])
        ];
    }

    private function getOrderAddress(
        ?string $label = null,
        ?string $firstName = null,
        ?string $lastName = null,
        ?string $phone = null,
        ?string $street = null
    ): OrderAddress {
        $orderAddress = new OrderAddress();
        $orderAddress->setLabel($label);
        $orderAddress->setFirstName($firstName);
        $orderAddress->setLastName($lastName);
        $orderAddress->setPhone($phone);
        $orderAddress->setStreet($street);

        return $orderAddress;
    }
}
