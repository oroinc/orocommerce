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
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CheckoutAddressTypeTest extends FormIntegrationTestCase
{
    /** @var OrderAddressToAddressIdentifierViewTransformer|\PHPUnit\Framework\MockObject\MockObject */
    private $orderAddressToAddressIdentifierViewTransformer;

    public function testGetBlockPrefix()
    {
        $type = new CheckoutAddressType();
        $this->assertEquals('oro_checkout_address', $type->getBlockPrefix());
    }

    public function testConfigureOptions()
    {
        /** @var OptionsResolver|\PHPUnit\Framework\MockObject\MockObject $resolver */
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
        $type = new CheckoutAddressType();

        $this->assertIsString($type->getParent());
        $this->assertEquals(OrderAddressType::class, $type->getParent());
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param OrderAddress|null $defaultData
     * @param array $submittedData
     * @param OrderAddress|null $expectedData
     */
    public function testSubmit($defaultData, $submittedData, $expectedData)
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

    /**
     * @return array
     */
    public function submitDataProvider()
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
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        $orderAddressSecurityProvider = $this->createMock(OrderAddressSecurityProvider::class);
        $orderAddressSecurityProvider->expects($this->any())
            ->method('isManualEditGranted')
            ->willReturn(true);
        $orderAddressType = new OrderAddressType($orderAddressSecurityProvider);
        $addressTypeStub = new AddressTypeStub();
        /** @var OrderAddressManager|\PHPUnit\Framework\MockObject\MockObject $addressManager */
        $addressManager = $this->createMock(OrderAddressManager::class);
        $addressManager->expects($this->any())
            ->method('getGroupedAddresses')
            ->willReturn(new TypedOrderAddressCollection(null, 'billing', []));
        $addressFormatter = $this->createMock(AddressFormatter::class);
        $serializer = $this->createMock(Serializer::class);
        $addressType = new EntityType(
            [
                AddressType::TYPE_BILLING => new AddressType(AddressType::TYPE_BILLING),
                AddressType::TYPE_SHIPPING => new AddressType(AddressType::TYPE_SHIPPING),
            ],
            TranslatableEntityType::NAME
        );
        $this->orderAddressToAddressIdentifierViewTransformer = $this->createMock(
            OrderAddressToAddressIdentifierViewTransformer::class
        );

        return [
            new PreloadedExtension(
                [
                    CheckoutAddressType::class => new CheckoutAddressType(),
                    OrderAddressType::class => $orderAddressType,
                    AddressFormType::class => $addressTypeStub,
                    CheckoutAddressSelectType::class => new CheckoutAddressSelectType(
                        $addressManager,
                        $this->orderAddressToAddressIdentifierViewTransformer
                    ),
                    OrderAddressSelectType::class => new OrderAddressSelectType(
                        $addressManager,
                        $addressFormatter,
                        $orderAddressSecurityProvider,
                        $serializer
                    ),
                    TranslatableEntityType::class => $addressType,
                ],
                [
                    FormType::class => [new StripTagsExtensionStub($this)],
                ]
            ),
        ];
    }

    /**
     * @param string|null $label
     * @param string|null $firstName
     * @param string|null $lastName
     * @param string|null $phone
     * @param string|null $street
     *
     * @return OrderAddress
     */
    private function getOrderAddress($label = null, $firstName = null, $lastName = null, $phone = null, $street = null)
    {
        $orderAddress = new OrderAddress();
        $orderAddress->setLabel($label);
        $orderAddress->setFirstName($firstName);
        $orderAddress->setLastName($lastName);
        $orderAddress->setPhone($phone);
        $orderAddress->setStreet($street);

        return $orderAddress;
    }
}
