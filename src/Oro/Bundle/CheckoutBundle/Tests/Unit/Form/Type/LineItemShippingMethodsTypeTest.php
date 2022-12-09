<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Form\Type\LineItemShippingMethodsType;
use Oro\Bundle\CheckoutBundle\Manager\MultiShipping\CheckoutLineItemsShippingManager;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LineItemShippingMethodsTypeTest extends FormIntegrationTestCase
{
    private CheckoutLineItemsShippingManager $shippingManager;

    protected function setUp(): void
    {
        $this->shippingManager = $this->createMock(CheckoutLineItemsShippingManager::class);
        parent::setUp();
    }

    public function testConfigureOptions()
    {
        $formType = new LineItemShippingMethodsType($this->shippingManager);

        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setRequired')
            ->with(['checkout'])
            ->willReturnSelf();

        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'data_class' => null,
                'checkout' => null
            ])
            ->willReturnSelf();

        $resolver->expects($this->once())
            ->method('setAllowedTypes')
            ->with('checkout', Checkout::class)
            ->willReturnSelf();

        $formType->configureOptions($resolver);
    }

    public function testOnSubmit()
    {
        $checkout = new Checkout();

        $lineItem1 = new CheckoutLineItem();
        ReflectionUtil::setId($lineItem1, 1);
        $checkout->addLineItem($lineItem1);

        $lineItem2 = new CheckoutLineItem();
        ReflectionUtil::setId($lineItem2, 2);
        $checkout->addLineItem($lineItem2);

        $formType = $this->factory->create(LineItemShippingMethodsType::class, null, [
            'checkout' => $checkout,
            'data' => []
        ]);

        $submitData = [
            1 => [
                'method' => 'SHIPPING_METHOD',
                'type' => 'SHIPPING_METHOD_TYPE'
            ],
            2 => [
                'method' => 'SHIPPING_METHOD_2',
                'type' => 'SHIPPING_METHOD_TYPE_2'
            ],
        ];

        $this->shippingManager->expects($this->once())
            ->method('updateLineItemsShippingMethods')
            ->with($submitData, $checkout);

        $formType->submit(json_encode($submitData));

        $this->assertTrue($formType->isValid());
        $this->assertTrue($formType->isSynchronized());
    }

    protected function getExtensions(): array
    {
        $formTypeInstance = new LineItemShippingMethodsType($this->shippingManager);

        return [
            new PreloadedExtension(
                [$formTypeInstance],
                []
            )
        ];
    }

    public function testGetBlockPrefix()
    {
        $formType = new LineItemShippingMethodsType($this->shippingManager);
        $this->assertEquals('oro_checkout_line_items_shipping_methods', $formType->getBlockPrefix());
    }

    public function testGetNamePrefix()
    {
        $formType = new LineItemShippingMethodsType($this->shippingManager);
        $this->assertEquals('oro_checkout_line_items_shipping_methods', $formType->getName());
    }

    public function testGetParent()
    {
        $formType = new LineItemShippingMethodsType($this->shippingManager);
        $this->assertEquals(HiddenType::class, $formType->getParent());
    }
}
