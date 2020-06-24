<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Form\Type\CheckoutAddressSelectType;
use Oro\Bundle\CustomerBundle\Entity\CustomerAddress;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress;
use Oro\Bundle\ImportExportBundle\Serializer\Serializer;
use Oro\Bundle\LocaleBundle\Formatter\AddressFormatter;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Form\Type\OrderAddressSelectType;
use Oro\Bundle\OrderBundle\Manager\OrderAddressManager;
use Oro\Bundle\OrderBundle\Manager\TypedOrderAddressCollection;
use Oro\Bundle\OrderBundle\Provider\OrderAddressSecurityProvider;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CheckoutAddressSelectTypeTest extends FormIntegrationTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|OrderAddressManager */
    private $orderAddressManager;

    /** @var OrderAddressSecurityProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $orderAddressSecurityProvider;

    /** @var PropertyAccessor|\PHPUnit\Framework\MockObject\MockObject */
    private $propertyAccessor;

    /** @var array */
    private $requiredFields;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->orderAddressManager = $this->createMock(OrderAddressManager::class);
        $this->orderAddressSecurityProvider = $this->createMock(OrderAddressSecurityProvider::class);
        $this->propertyAccessor = $this->createMock(PropertyAccessor::class);
        $this->requiredFields = ['street', 'city', 'country', 'region', 'postalCode'];

        parent::setUp();
    }

    public function testGetBlockPrefix()
    {
        $type = new CheckoutAddressSelectType(
            $this->orderAddressManager,
            $this->propertyAccessor,
            $this->requiredFields
        );
        $this->assertEquals('oro_checkout_address_select', $type->getBlockPrefix());
    }

    public function testConfigureOptions()
    {
        /** @var OptionsResolver|\PHPUnit\Framework\MockObject\MockObject $resolver */
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'data' => null,
                'data_class' => null,
                'group_label_prefix' => 'oro.checkout.',
            ])
            ->willReturnSelf();
        $resolver->expects($this->once())
            ->method('setAllowedTypes')
            ->with('object', Checkout::class)
            ->willReturnSelf();

        $type = new CheckoutAddressSelectType(
            $this->orderAddressManager,
            $this->propertyAccessor,
            $this->requiredFields
        );
        $type->configureOptions($resolver);
    }

    public function testBuildForm()
    {
        $this->orderAddressManager->expects($this->once())
            ->method('getGroupedAddresses')
            ->willReturn(new TypedOrderAddressCollection(null, 'billing', []));

        $form = $this->factory->create(CheckoutAddressSelectType::class, null, [
            'object' => new Checkout(),
            'address_type' => 'billing'
        ]);

        $this->assertTrue($form->getConfig()->hasAttribute('choice_list'));
    }

    public function testGetParent()
    {
        $type = new CheckoutAddressSelectType(
            $this->orderAddressManager,
            $this->propertyAccessor,
            $this->requiredFields
        );

        $this->assertIsString($type->getParent());
        $this->assertEquals(OrderAddressSelectType::class, $type->getParent());
    }

    public function testSubmit()
    {
        $key = 'ca_1';
        $customerAddress = new CustomerAddress();
        $orderAddress = new OrderAddress();
        $orderAddress->setLabel('Customer Address #1');

        $this->orderAddressManager->expects($this->once())
            ->method('updateFromAbstract')
            ->with($customerAddress)
            ->willReturn($orderAddress);
        $this->orderAddressManager->expects($this->once())
            ->method('getGroupedAddresses')
            ->willReturn(new TypedOrderAddressCollection(null, 'billing', [
                'Customer Addresses' => [
                    $key => $customerAddress
                ]
            ]));
        $this->orderAddressManager->expects($this->any())
            ->method('getIdentifier')
            ->with($customerAddress)
            ->willReturn($key);

        $form = $this->factory->create(CheckoutAddressSelectType::class, null, [
            'object' => new Checkout(),
            'address_type' => 'billing'
        ]);

        $form->submit($key);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());

        $this->assertEquals($orderAddress, $form->getData());
    }

    /**
     * @dataProvider submitWhenEnterManuallyDataProvider
     *
     * @param Checkout $checkout
     * @param OrderAddress $orderAddress
     * @param string $addressType
     */
    public function testSubmitWhenEnterManually(
        Checkout $checkout,
        OrderAddress $orderAddress,
        string $addressType
    ): void {
        $this->orderAddressSecurityProvider
            ->expects($this->any())
            ->method('isManualEditGranted')
            ->willReturn(true);

        $this->orderAddressManager->expects($this->once())
            ->method('getGroupedAddresses')
            ->willReturn(new TypedOrderAddressCollection(null, 'billing', [
                'Customer Addresses' => ['ca_1' => new CustomerAddress()]
            ]));

        $this->orderAddressManager
            ->expects($this->never())
            ->method('updateFromAbstract');

        $form = $this->factory->create(CheckoutAddressSelectType::class, null, [
            'object' => $checkout,
            'address_type' => $addressType,
        ]);

        $form->submit(0);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());

        $this->assertEquals($orderAddress, $form->getData());
    }

    /**
     * @return array
     */
    public function submitWhenEnterManuallyDataProvider(): array
    {
        $orderAddress = new OrderAddress();
        return [
            [
                'checkout' => (new Checkout())->setBillingAddress($orderAddress),
                'orderAddress' => $orderAddress,
                'addressType' => 'billing',
            ],
            [
                'checkout' => (new Checkout())->setShippingAddress($orderAddress),
                'orderAddress' => $orderAddress,
                'addressType' => 'shipping',
            ],
        ];
    }

    /**
     * @dataProvider submitWhenEnterManuallyButAlreadySetInCheckoutDataProvider
     *
     * @param Checkout $checkout
     * @param string $addressType
     */
    public function testSubmitWhenEnterManuallyButAlreadySetInCheckout(Checkout $checkout, string $addressType): void
    {
        $this->orderAddressSecurityProvider
            ->expects($this->any())
            ->method('isManualEditGranted')
            ->willReturn(true);

        $this->orderAddressManager->expects($this->once())
            ->method('getGroupedAddresses')
            ->willReturn(new TypedOrderAddressCollection(null, 'billing', [
                'Customer Addresses' => ['ca_1' => new CustomerAddress()]
            ]));

        $this->orderAddressManager
            ->expects($this->never())
            ->method('updateFromAbstract');

        $form = $this->factory->create(CheckoutAddressSelectType::class, null, [
            'object' => $checkout,
            'address_type' => $addressType,
        ]);

        $form->submit(0);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());

        $this->assertEquals(0, $form->getData());
    }

    /**
     * @return array
     */
    public function submitWhenEnterManuallyButAlreadySetInCheckoutDataProvider(): array
    {
        $orderAddress = new OrderAddress();
        $customerAddress = new CustomerAddress();
        $customerUserAddress = new CustomerUserAddress();

        return [
            [
                'checkout' => (new Checkout())->setBillingAddress(
                    (clone $orderAddress)
                        ->setCustomerAddress($customerAddress)
                        ->setCustomerUserAddress($customerUserAddress)
                ),
                'addressType' => 'billing',
            ],
            [
                'checkout' => (new Checkout())->setBillingAddress(
                    (clone $orderAddress)
                        ->setCustomerAddress($customerAddress)
                        ->setCustomerUserAddress($customerUserAddress)
                ),
                'addressType' => 'shipping',
            ],
            [
                'checkout' => (new Checkout())->setBillingAddress(
                    (clone $orderAddress)->setCustomerAddress($customerAddress)
                ),
                'addressType' => 'billing',
            ],
            [
                'checkout' => (new Checkout())->setBillingAddress(
                    (clone $orderAddress)->setCustomerUserAddress($customerUserAddress)
                ),
                'addressType' => 'billing',
            ],
            [
                'checkout' => (new Checkout())->setShippingAddress(
                    (clone $orderAddress)->setCustomerAddress($customerAddress)
                ),
                'addressType' => 'shipping',
            ],
            [
                'checkout' => (new Checkout())->setShippingAddress(
                    (clone $orderAddress)->setCustomerUserAddress($customerUserAddress)
                ),
                'addressType' => 'shipping',
            ],
            [
                'checkout' => (new Checkout()),
                'addressType' => 'shipping',
            ],
            [
                'checkout' => (new Checkout()),
                'addressType' => 'billing',
            ],
        ];
    }

    /**
     * @dataProvider enterManuallySelectedByDefaultDataProvider
     */
    public function testEnterManuallySelectedByDefault($address, $expected)
    {
        $checkout = new Checkout();
        $checkout->setBillingAddress($address);

        $this->orderAddressManager->expects($this->once())
            ->method('getGroupedAddresses')
            ->willReturn(new TypedOrderAddressCollection(null, 'billing', []));

        $this->propertyAccessor->expects($this->any())
            ->method('getValue')
            ->will($this->returnValueMap(
                [
                    [$address, 'street', $address->getStreet()],
                    [$address, 'city', $address->getCity()],
                    [$address, 'country', $address->getCountry()],
                    [$address, 'region', $address->getRegion()],
                    [$address, 'postalCode', $address->getPostalCode()]
                ]
            ));

        $form = $this->factory->create(CheckoutAddressSelectType::class, null, [
            'object'       => $checkout,
            'address_type' => 'billing'
        ]);

        $this->assertSame($expected, $form->getData());
    }

    public function enterManuallySelectedByDefaultDataProvider()
    {
        return [
            'If address is empty default value should be null' => [
                'address'  => new OrderAddress(),
                'expected' => null
            ],
            'If address has fulfilled fields which are not required default value should be null' => [
                'addresss' => (new OrderAddress())->setCreated(new \DateTime()),
                'expected' => null
            ],
            'If address has fulfilled one of the required fields default value should be 0' => [
                'address'  => (new OrderAddress())->setCity('Los Angeles')->setCreated(new \DateTime()),
                'expected' => 0
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        $addressFormatter = $this->createMock(AddressFormatter::class);
        $serializer = $this->createMock(Serializer::class);

        return [
            new PreloadedExtension([
                CheckoutAddressSelectType::class => new CheckoutAddressSelectType(
                    $this->orderAddressManager,
                    $this->propertyAccessor,
                    $this->requiredFields
                ),
                OrderAddressSelectType::class => new OrderAddressSelectType(
                    $this->orderAddressManager,
                    $addressFormatter,
                    $this->orderAddressSecurityProvider,
                    $serializer
                ),
            ], [
            ])
        ];
    }
}
