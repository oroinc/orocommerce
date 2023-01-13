<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Form\DataTransformer\OrderAddressToAddressIdentifierViewTransformer;
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

/**
 * Form type for Checkout Address with selector.
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CheckoutAddressSelectTypeTest extends FormIntegrationTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|OrderAddressManager */
    private $orderAddressManager;

    /** @var OrderAddressSecurityProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $orderAddressSecurityProvider;

    /** @var OrderAddressToAddressIdentifierViewTransformer|\PHPUnit\Framework\MockObject\MockObject */
    private $orderAddressToAddressIdentifierViewTransformer;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->orderAddressManager = $this->createMock(OrderAddressManager::class);
        $this->orderAddressSecurityProvider = $this->createMock(OrderAddressSecurityProvider::class);
        $this->orderAddressToAddressIdentifierViewTransformer = $this->createMock(
            OrderAddressToAddressIdentifierViewTransformer::class
        );

        parent::setUp();
    }

    public function testGetBlockPrefix(): void
    {
        $type = new CheckoutAddressSelectType(
            $this->orderAddressManager,
            $this->orderAddressToAddressIdentifierViewTransformer
        );
        $this->assertEquals('oro_checkout_address_select', $type->getBlockPrefix());
    }

    public function testConfigureOptions(): void
    {
        /** @var OptionsResolver|\PHPUnit\Framework\MockObject\MockObject $resolver */
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'data' => null,
                'data_class' => null,
                'group_label_prefix' => 'oro.checkout.'
            ])
            ->willReturnSelf();
        $resolver->expects($this->once())
            ->method('setAllowedTypes')
            ->with('object', Checkout::class)
            ->willReturnSelf();

        $type = new CheckoutAddressSelectType(
            $this->orderAddressManager,
            $this->orderAddressToAddressIdentifierViewTransformer
        );
        $type->configureOptions($resolver);
    }

    public function testBuildForm(): void
    {
        $this->orderAddressManager->expects($this->once())
            ->method('getGroupedAddresses')
            ->willReturn(new TypedOrderAddressCollection(null, 'billing', []));

        $form = $this->factory->create(CheckoutAddressSelectType::class, null, [
            'object' => new Checkout(),
            'address_type' => 'billing',
            'disabled' => null,
        ]);

        $this->assertTrue($form->getConfig()->hasAttribute('choice_list'));
        $this->assertTrue($form->getConfig()->hasOption('disabled'));
        $this->assertFalse($form->getConfig()->getOption('disabled'));
    }

    public function testGetParent(): void
    {
        $type = new CheckoutAddressSelectType(
            $this->orderAddressManager,
            $this->orderAddressToAddressIdentifierViewTransformer
        );

        $this->assertEquals(OrderAddressSelectType::class, $type->getParent());
    }

    public function testSubmit(): void
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

        $this->orderAddressToAddressIdentifierViewTransformer
            ->expects($this->once())
            ->method('reverseTransform')
            ->with($customerAddress)
            ->willReturn($customerAddress);

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
     * {@inheritdoc}
     */
    protected function getExtensions(): array
    {
        $addressFormatter = $this->createMock(AddressFormatter::class);
        $serializer = $this->createMock(Serializer::class);

        $formTypeInstance = new CheckoutAddressSelectType(
            $this->orderAddressManager,
            $this->orderAddressToAddressIdentifierViewTransformer
        );

        return [
            new PreloadedExtension([
                CheckoutAddressSelectType::class => $formTypeInstance,
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
