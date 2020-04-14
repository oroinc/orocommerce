<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\CustomerBundle\Entity\CustomerAddress;
use Oro\Bundle\CustomerBundle\Entity\CustomerOwnerAwareInterface;
use Oro\Bundle\FormBundle\Form\Type\Select2ChoiceType;
use Oro\Bundle\ImportExportBundle\Serializer\Serializer;
use Oro\Bundle\LocaleBundle\Formatter\AddressFormatter;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Form\Type\OrderAddressSelectType;
use Oro\Bundle\OrderBundle\Manager\OrderAddressManager;
use Oro\Bundle\OrderBundle\Manager\TypedOrderAddressCollection;
use Oro\Bundle\OrderBundle\Provider\OrderAddressSecurityProvider;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderAddressSelectTypeTest extends FormIntegrationTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|OrderAddressManager */
    private $orderAddressManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|OrderAddressSecurityProvider */
    private $orderAddressSecurityProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|AddressFormatter */
    private $addressFormatter;

    /** @var \PHPUnit\Framework\MockObject\MockObject|Serializer */
    private $serializer;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->orderAddressManager = $this->createMock(OrderAddressManager::class);
        $this->orderAddressSecurityProvider = $this->createMock(OrderAddressSecurityProvider::class);
        $this->addressFormatter = $this->createMock(AddressFormatter::class);
        $this->serializer = $this->createMock(Serializer::class);

        parent::setUp();
    }

    public function testGetBlockPrefix()
    {
        $type = new OrderAddressSelectType(
            $this->orderAddressManager,
            $this->addressFormatter,
            $this->orderAddressSecurityProvider,
            $this->serializer
        );
        $this->assertEquals('oro_order_address_select', $type->getBlockPrefix());
    }

    public function testConfigureOptions()
    {
        /** @var OptionsResolver|\PHPUnit\Framework\MockObject\MockObject $resolver */
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setRequired')
            ->with(['object', 'address_type'])
            ->willReturnSelf();
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'data_class' => OrderAddress::class,
                'label' => false,
                'configs' => function () {
                },
                'address_collection' => function () {
                },
                'choice_loader' => function () {
                },
                'choice_value' => function () {
                },
                'choice_label' => function () {
                },
                'group_label_prefix' => 'oro.order.',
            ])
            ->willReturnSelf();
        $resolver->expects($this->once())
            ->method('setAllowedValues')
            ->with('address_type', [AddressType::TYPE_BILLING, AddressType::TYPE_SHIPPING])
            ->willReturnSelf();
        $resolver->expects($this->once())
            ->method('setAllowedTypes')
            ->with('object', CustomerOwnerAwareInterface::class)
            ->willReturnSelf();

        $type = new OrderAddressSelectType(
            $this->orderAddressManager,
            $this->addressFormatter,
            $this->orderAddressSecurityProvider,
            $this->serializer
        );
        $type->configureOptions($resolver);
    }

    public function testBuildForm()
    {
        $this->orderAddressManager->expects($this->once())
            ->method('getGroupedAddresses')
            ->willReturn(new TypedOrderAddressCollection(null, 'billing', []));

        $form = $this->factory->create(OrderAddressSelectType::class, null, [
            'object' => new Order(),
            'address_type' => 'billing'
        ]);

        $this->assertTrue($form->getConfig()->hasAttribute('choice_list'));
    }

    public function testGetParent()
    {
        $type = new OrderAddressSelectType(
            $this->orderAddressManager,
            $this->addressFormatter,
            $this->orderAddressSecurityProvider,
            $this->serializer
        );

        $this->assertIsString($type->getParent());
        $this->assertEquals(Select2ChoiceType::class, $type->getParent());
    }

    public function testSubmit()
    {
        $key = 'ca_1';
        $orderAddress = new OrderAddress();
        $orderAddress->setLabel('Customer Address #1');
        $customerAddress = new CustomerAddress();

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

        $form = $this->factory->create(OrderAddressSelectType::class, null, [
            'object' => new Order(),
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
        return [
            new PreloadedExtension([
                OrderAddressSelectType::class => new OrderAddressSelectType(
                    $this->orderAddressManager,
                    $this->addressFormatter,
                    $this->orderAddressSecurityProvider,
                    $this->serializer
                ),
            ], [
            ])
        ];
    }
}
