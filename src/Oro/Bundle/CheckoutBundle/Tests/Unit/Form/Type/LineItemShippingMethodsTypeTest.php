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

class LineItemShippingMethodsTypeTest extends FormIntegrationTestCase
{
    /** @var CheckoutLineItemsShippingManager|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingManager;

    /** @var LineItemShippingMethodsType */
    private $formType;

    protected function setUp(): void
    {
        $this->shippingManager = $this->createMock(CheckoutLineItemsShippingManager::class);
        $this->formType = new LineItemShippingMethodsType($this->shippingManager);

        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension([$this->formType], [])
        ];
    }

    public function testSubmit(): void
    {
        $checkout = new Checkout();

        $lineItem1 = new CheckoutLineItem();
        ReflectionUtil::setId($lineItem1, 1);
        $checkout->addLineItem($lineItem1);

        $lineItem2 = new CheckoutLineItem();
        ReflectionUtil::setId($lineItem2, 2);
        $checkout->addLineItem($lineItem2);

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

        $form = $this->factory->create(LineItemShippingMethodsType::class, null, [
            'checkout' => $checkout,
            'data' => []
        ]);
        $form->submit(json_encode($submitData, JSON_THROW_ON_ERROR));

        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
    }

    public function testGetBlockPrefix(): void
    {
        $this->assertEquals('oro_checkout_line_items_shipping_methods', $this->formType->getBlockPrefix());
    }

    public function testGetParent(): void
    {
        $this->assertEquals(HiddenType::class, $this->formType->getParent());
    }
}
