<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Form\Extension;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Form\Section\SectionProvider;
use Oro\Bundle\PromotionBundle\Provider\OrdersAppliedDiscountsProvider;
use Oro\Bundle\PromotionBundle\Form\Extension\OrderLineItemTypeExtension;
use Oro\Bundle\TaxBundle\Manager\TaxManager;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;

class OrderLineItemTypeExtensionTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var TaxationSettingsProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $taxationSettingsProvider;

    /**
     * @var TaxManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $taxManager;

    /**
     * @var OrdersAppliedDiscountsProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $ordersAppliedDiscountProvider;

    /**
     * @var SectionProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $sectionProvider;

    /**
     * @var LineItemSubtotalProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $lineItemSubtotalProvider;

    /**
     * @var OrderLineItemTypeExtension
     */
    protected $extension;

    protected function setUp()
    {
        $this->taxationSettingsProvider = $this->createMock(TaxationSettingsProvider::class);
        $this->taxManager = $this->createMock(TaxManager::class);
        $this->ordersAppliedDiscountProvider = $this->createMock(OrdersAppliedDiscountsProvider::class);
        $this->sectionProvider = $this->createMock(SectionProvider::class);
        $this->lineItemSubtotalProvider = $this->createMock(LineItemSubtotalProvider::class);

        $this->extension = new OrderLineItemTypeExtension(
            $this->taxationSettingsProvider,
            $this->taxManager,
            $this->ordersAppliedDiscountProvider,
            $this->sectionProvider,
            $this->lineItemSubtotalProvider
        );
    }

    public function testGetExtendedType()
    {
        $this->assertEquals('oro_order_line_item', $this->extension->getExtendedType());
    }

    public function testBuildView()
    {
        $this->sectionProvider->expects($this->once())->method('addSections')
            ->with(
                $this->equalTo('oro_order_line_item'),
                $this->logicalAnd(
                    $this->isType('array'),
                    $this->arrayHasKey('applied_discounts')
                )
            );

        $view = new FormView();
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->createMock(FormInterface::class);

        $this->extension->buildView($view, $form, []);
    }

    /**
     * @dataProvider finishViewDataProvider
     * @param array $sourceData
     * @param array $expectedData
     */
    public function testFinishView(array $sourceData, $expectedData)
    {
        $orderLineItem = $this->getEntity(OrderLineItem::class, ['id' => 1]);
        $orderLineItem->setCurrency($sourceData['currency']);
        $orderLineItem->setValue($sourceData['price']);
        $orderLineItem->setQuantity($sourceData['quantity']);

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())->method('getData')->willReturn($orderLineItem);

        $this->ordersAppliedDiscountProvider
            ->expects($this->once())
            ->method('getDiscountsAmountByLineItem')
            ->with($orderLineItem)
            ->willReturn($sourceData['discountAmount']);

        $this->taxationSettingsProvider->expects($this->once())->method('isEnabled')->willReturn(true);

        $this->taxManager
            ->expects($this->once())
            ->method('getTax')
            ->with($orderLineItem)
            ->willReturn($sourceData['taxes']);

        $this->lineItemSubtotalProvider->expects($this->once())
            ->method('getRowTotal')
            ->with($orderLineItem)
            ->willReturnCallback(function (OrderLineItem $orderLineItem, $currency) {
                return $orderLineItem->getValue() * $orderLineItem->getQuantity();
            });

        $view = new FormView();

        $this->extension->finishView($view, $form, []);

        $this->assertEquals($expectedData, $view->vars['applied_discounts']);
    }

    public function testFinishViewWithoutEntity()
    {
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())->method('getData')->willReturn(null);

        $this->ordersAppliedDiscountProvider
            ->expects($this->never())
            ->method('getDiscountsAmountByLineItem');

        $this->taxationSettingsProvider->expects($this->never())->method('isEnabled');

        $this->taxManager
            ->expects($this->never())
            ->method('getTax');

        $view = new FormView();

        $this->extension->finishView($view, $form, []);
    }

    /**
     * @dataProvider finishViewWithDisabledTaxesDataProvider
     * @param array $sourceData
     * @param array $expectedData
     */
    public function testFinishViewWithDisabledTaxes(array $sourceData, $expectedData)
    {
        $orderLineItem = $this->getEntity(OrderLineItem::class, ['id' => 1]);
        $orderLineItem->setCurrency($sourceData['currency']);
        $orderLineItem->setValue($sourceData['price']);
        $orderLineItem->setQuantity($sourceData['quantity']);

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())->method('getData')->willReturn($orderLineItem);

        $this->ordersAppliedDiscountProvider
            ->expects($this->once())
            ->method('getDiscountsAmountByLineItem')
            ->with($orderLineItem)
            ->willReturn($sourceData['discountAmount']);

        $this->taxationSettingsProvider->expects($this->once())->method('isEnabled')->willReturn(false);

        $this->taxManager
            ->expects($this->never())
            ->method('getTax');

        $this->lineItemSubtotalProvider->expects($this->once())
            ->method('getRowTotal')
            ->with($orderLineItem)
            ->willReturnCallback(function (OrderLineItem $orderLineItem, $currency) {
                return $orderLineItem->getValue() * $orderLineItem->getQuantity();
            });

        $view = new FormView();

        $this->extension->finishView($view, $form, []);

        $this->assertEquals($expectedData, $view->vars['applied_discounts']);
    }

    /**
     * @return array
     */
    public function finishViewDataProvider()
    {
        return [
            [
                'sourceData' => [
                    'currency' => 'USD',
                    'price' => 1.34,
                    'quantity' => 7,
                    'discountAmount' => 3.0,
                    'taxes' => [1, 2, 5],
                ],
                'expectedData' => [
                    'currency' => 'USD',
                    'discountAmount' => 3.0,
                    'rowTotalWithoutDiscount' => 9.38,
                    'taxes' => [1, 2, 5],
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function finishViewWithDisabledTaxesDataProvider()
    {
        return [
            [
                'sourceData' => [
                    'currency' => 'USD',
                    'price' => 1.34,
                    'quantity' => 7,
                    'discountAmount' => 3,
                ],
                'expectedData' => [
                    'currency' => 'USD',
                    'discountAmount' => 3.0,
                    'rowTotalWithoutDiscount' => 9.38,
                ],
            ],
        ];
    }
}
