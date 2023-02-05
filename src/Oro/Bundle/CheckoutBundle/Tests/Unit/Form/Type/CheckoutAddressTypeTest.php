<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\AddressBundle\Form\Type\AddressType as AddressFormType;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Form\DataTransformer\OrderAddressToAddressIdentifierViewTransformer;
use Oro\Bundle\CheckoutBundle\Form\Type\CheckoutAddressSelectType;
use Oro\Bundle\CheckoutBundle\Form\Type\CheckoutAddressType;
use Oro\Bundle\CustomerBundle\Entity\CustomerAddress;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress;
use Oro\Bundle\CustomerBundle\Tests\Unit\Form\Type\Stub\AddressTypeStub;
use Oro\Bundle\FormBundle\Tests\Unit\Stub\StripTagsExtensionStub;
use Oro\Bundle\ImportExportBundle\Serializer\Serializer;
use Oro\Bundle\LocaleBundle\Formatter\AddressFormatter;
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

class CheckoutAddressTypeTest extends FormIntegrationTestCase
{
    public function testGetBlockPrefix()
    {
        $type = new CheckoutAddressType();
        $this->assertEquals('oro_checkout_address', $type->getBlockPrefix());
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setAllowedTypes')
            ->with('object', Checkout::class)
            ->willReturnSelf();

        $type = new CheckoutAddressType();
        $type->configureOptions($resolver);
    }

    public function testBuildForm()
    {
        $form = $this->factory->create(CheckoutAddressType::class, null, [
            'object' => new Checkout(),
            'addressType' => 'billing',
            'disabled' => null,
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
        $this->assertTrue($form->getConfig()->hasOption('disabled'));
        $this->assertFalse($form->getConfig()->getOption('disabled'));
    }

    public function testGetParent()
    {
        $type = new CheckoutAddressType();

        $this->assertIsString($type->getParent());
        $this->assertEquals(OrderAddressType::class, $type->getParent());
    }

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmit(OrderAddress $defaultData, array $submittedData, OrderAddress $expectedData)
    {
        $form = $this->factory->create(CheckoutAddressType::class, $defaultData, [
            'object' => new Checkout(),
            'addressType' => 'billing'
        ]);

        $this->assertEquals($defaultData, $form->getViewData());
        $this->assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());

        $this->assertEquals($expectedData, $form->getData());
    }

    public function submitDataProvider(): array
    {
        return [
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
            ],
            'new order address' => [
                'defaultData' => $this->getOrderAddress(),
                'submittedData' => [
                    'label' => 'new address'
                ],
                'expectedData' => $this->getOrderAddress('new address'),
            ],
        ];
    }

    /**
     * @dataProvider preSetDataDataProvider
     */
    public function testPreSetData(?OrderAddress $orderAddress, ?OrderAddress $expectedAddress): void
    {
        $form = $this->factory->create(CheckoutAddressType::class, $orderAddress, [
            'object' => new Checkout(),
            'addressType' => 'billing'
        ]);

        $this->assertEquals($expectedAddress, $form->getData());
    }

    public function preSetDataDataProvider(): array
    {
        $orderAddress = $this->getOrderAddress('sample address');

        return [
            ['orderAddress' => $orderAddress, 'expectedOrderAddress' => $orderAddress],
            ['orderAddress' => null, 'expectedOrderAddress' => null],
            [
                'orderAddress' => (clone $orderAddress)->setCustomerAddress(new CustomerAddress()),
                'expectedOrderAddress' => null,
            ],
            [
                'orderAddress' => (clone $orderAddress)->setCustomerUserAddress(new CustomerUserAddress()),
                'expectedOrderAddress' => null,
            ],
            [
                'orderAddress' => (clone $orderAddress)
                    ->setCustomerUserAddress(new CustomerUserAddress())
                    ->setCustomerAddress(new CustomerAddress()),
                'expectedOrderAddress' => null,
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        $orderAddressSecurityProvider = $this->createMock(OrderAddressSecurityProvider::class);
        $orderAddressSecurityProvider->expects($this->any())
            ->method('isManualEditGranted')
            ->willReturn(true);

        $addressManager = $this->createMock(OrderAddressManager::class);
        $addressManager->expects($this->any())
            ->method('getGroupedAddresses')
            ->willReturn(new TypedOrderAddressCollection(null, 'billing', []));

        return [
            new PreloadedExtension(
                [
                    new CheckoutAddressType(),
                    new OrderAddressType($orderAddressSecurityProvider),
                    AddressFormType::class => new AddressTypeStub(),
                    new CheckoutAddressSelectType(
                        $addressManager,
                        $this->createMock(OrderAddressToAddressIdentifierViewTransformer::class)
                    ),
                    new OrderAddressSelectType(
                        $addressManager,
                        $this->createMock(AddressFormatter::class),
                        $orderAddressSecurityProvider,
                        $this->createMock(Serializer::class)
                    ),
                    TranslatableEntityType::class => new EntityTypeStub([
                        AddressType::TYPE_BILLING => new AddressType(AddressType::TYPE_BILLING),
                        AddressType::TYPE_SHIPPING => new AddressType(AddressType::TYPE_SHIPPING),
                    ]),
                ],
                [
                    FormType::class => [new StripTagsExtensionStub($this)],
                ]
            ),
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
