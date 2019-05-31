<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Form\Type\CheckoutAddressSelectType;
use Oro\Bundle\CustomerBundle\Entity\CustomerAddress;
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

class CheckoutAddressSelectTypeTest extends FormIntegrationTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|OrderAddressManager */
    private $orderAddressManager;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->orderAddressManager = $this->createMock(OrderAddressManager::class);

        parent::setUp();
    }

    public function testGetBlockPrefix()
    {
        $type = new CheckoutAddressSelectType($this->orderAddressManager);
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

        $type = new CheckoutAddressSelectType($this->orderAddressManager);
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
        $type = new CheckoutAddressSelectType($this->orderAddressManager);

        $this->assertInternalType('string', $type->getParent());
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
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        $orderAddressSecurityProvider = $this->createMock(OrderAddressSecurityProvider::class);
        $addressFormatter = $this->createMock(AddressFormatter::class);
        $serializer = $this->createMock(Serializer::class);

        return [
            new PreloadedExtension([
                CheckoutAddressSelectType::class => new CheckoutAddressSelectType($this->orderAddressManager),
                OrderAddressSelectType::class => new OrderAddressSelectType(
                    $this->orderAddressManager,
                    $addressFormatter,
                    $orderAddressSecurityProvider,
                    $serializer
                ),
            ], [
            ])
        ];
    }
}
