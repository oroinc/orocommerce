<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Form\Type\LineItemGroupShippingMethodsType;
use Oro\Bundle\CheckoutBundle\Manager\MultiShipping\CheckoutLineItemGroupsShippingManager;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class LineItemGroupShippingMethodsTypeTest extends FormIntegrationTestCase
{
    /** @var CheckoutLineItemGroupsShippingManager|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingManager;

    /** @var LineItemGroupShippingMethodsType */
    private $formType;

    protected function setUp(): void
    {
        $this->shippingManager = $this->createMock(CheckoutLineItemGroupsShippingManager::class);
        $this->formType = new LineItemGroupShippingMethodsType($this->shippingManager);

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
            'product.category:1' => ['method' => 'method1', 'type' => 'type1'],
            'product.category:2' => ['method' => 'method2', 'type' => 'type2']
        ];

        $this->shippingManager->expects(self::once())
            ->method('updateLineItemGroupsShippingMethods')
            ->with($submitData, self::identicalTo($checkout));

        $form = $this->factory->create(LineItemGroupShippingMethodsType::class, null, [
            'checkout' => $checkout,
            'data' => []
        ]);
        $form->submit(json_encode($submitData, JSON_THROW_ON_ERROR));

        self::assertTrue($form->isValid());
        self::assertTrue($form->isSynchronized());
    }

    public function testGetBlockPrefix(): void
    {
        self::assertEquals('oro_checkout_line_items_group_shipping_methods', $this->formType->getBlockPrefix());
    }

    public function testGetParent(): void
    {
        self::assertEquals(HiddenType::class, $this->formType->getParent());
    }
}
