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
use Oro\Component\Layout\Extension\Theme\Model\CurrentThemeProvider;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityTypeStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CheckoutAddressTypeTest extends FormIntegrationTestCase
{
    private CurrentThemeProvider&MockObject $currentThemeProvider;
    private ThemeManager&MockObject $themeManager;

    #[\Override]
    protected function setUp(): void
    {
        $this->currentThemeProvider = $this->createMock(CurrentThemeProvider::class);
        $this->themeManager = $this->createMock(ThemeManager::class);

        parent::setUp();
    }

    public function testGetBlockPrefix(): void
    {
        $type = new CheckoutAddressType($this->currentThemeProvider, $this->themeManager);
        self::assertEquals('oro_checkout_address', $type->getBlockPrefix());
    }

    public function testConfigureOptions(): void
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects(self::once())
            ->method('setDefault')
            ->with('multiStepCheckout', false)
            ->willReturnSelf();

        $resolver->expects(self::exactly(2))
            ->method('setAllowedTypes')
            ->withConsecutive(
                ['object', Checkout::class],
                ['multiStepCheckout', 'bool']
            )
            ->willReturnSelf();

        $type = new CheckoutAddressType($this->currentThemeProvider, $this->themeManager);
        $type->configureOptions($resolver);
    }

    public function testBuildForm(): void
    {
        $form = $this->factory->create(CheckoutAddressType::class, null, [
            'object' => new Checkout(),
            'addressType' => 'billing',
            'disabled' => null,
        ]);

        self::assertTrue($form->has('id'));
        self::assertTrue($form->has('label'));
        self::assertTrue($form->has('namePrefix'));
        self::assertTrue($form->has('firstName'));
        self::assertTrue($form->has('middleName'));
        self::assertTrue($form->has('lastName'));
        self::assertTrue($form->has('nameSuffix'));
        self::assertTrue($form->has('organization'));
        self::assertTrue($form->has('country'));
        self::assertTrue($form->has('street'));
        self::assertTrue($form->has('city'));
        self::assertTrue($form->has('region'));
        self::assertTrue($form->has('postalCode'));
        self::assertTrue($form->has('customerAddress'));
        self::assertTrue($form->has('phone'));
        self::assertTrue($form->getConfig()->hasOption('disabled'));
        self::assertFalse($form->getConfig()->getOption('disabled'));
    }

    public function testGetParent(): void
    {
        $type = new CheckoutAddressType($this->currentThemeProvider, $this->themeManager);

        self::assertIsString($type->getParent());
        self::assertEquals(OrderAddressType::class, $type->getParent());
    }

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmit(OrderAddress $defaultData, array $submittedData, OrderAddress $expectedData): void
    {
        $form = $this->factory->create(CheckoutAddressType::class, $defaultData, [
            'object' => new Checkout(),
            'addressType' => 'billing'
        ]);

        self::assertEquals($defaultData, $form->getViewData());
        self::assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);
        self::assertTrue($form->isValid());
        self::assertTrue($form->isSynchronized());

        self::assertEquals($expectedData, $form->getData());
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

        self::assertEquals($expectedAddress, $form->getData());
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

    public function testPreSubmitOldTheme(): void
    {
        $this->currentThemeProvider->expects(self::once())
            ->method('getCurrentThemeId')
            ->willReturn('default');

        $this->themeManager->expects(self::once())
            ->method('themeHasParent')
            ->with('default', ['default_50', 'default_51'])
            ->willReturn(true);

        $form = $this->factory->create(CheckoutAddressType::class, $this->getOrderAddress(), [
            'object' => new Checkout(),
            'addressType' => 'billing',
            'multiStepCheckout' => true
        ]);

        $form->submit(['label' => 'test']);

        self::assertTrue($form->isValid());
        self::assertTrue($form->isSynchronized());

        self::assertTrue($form->get('label')->getConfig()->getMapped());
    }

    public function testPreSubmit(): void
    {
        $form = $this->factory->create(CheckoutAddressType::class, $this->getOrderAddress(), [
            'object' => new Checkout(),
            'addressType' => 'billing',
            'multiStepCheckout' => true
        ]);

        $form->submit(['label' => 'test', 'customerAddress' => '0']);

        self::assertTrue($form->isValid());
        self::assertTrue($form->isSynchronized());

        foreach ($form->all() as $child) {
            self::assertFalse($child->getConfig()->getMapped());
        }
    }

    #[\Override]
    protected function getExtensions(): array
    {
        $orderAddressSecurityProvider = $this->createMock(OrderAddressSecurityProvider::class);
        $orderAddressSecurityProvider->expects(self::any())
            ->method('isManualEditGranted')
            ->willReturn(true);

        $addressManager = $this->createMock(OrderAddressManager::class);
        $addressManager->expects(self::any())
            ->method('getGroupedAddresses')
            ->willReturn(new TypedOrderAddressCollection(null, 'billing', []));

        return [
            new PreloadedExtension(
                [
                    new CheckoutAddressType($this->currentThemeProvider, $this->themeManager),
                    new OrderAddressType(
                        $orderAddressSecurityProvider
                    ),
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
