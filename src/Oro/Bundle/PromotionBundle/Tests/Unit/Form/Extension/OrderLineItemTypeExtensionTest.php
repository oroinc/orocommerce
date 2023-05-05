<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Form\Section\SectionProvider;
use Oro\Bundle\OrderBundle\Form\Type\OrderLineItemType;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider;
use Oro\Bundle\PromotionBundle\Form\Extension\OrderLineItemTypeExtension;
use Oro\Bundle\PromotionBundle\Provider\AppliedDiscountsProvider;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;
use Oro\Bundle\TaxBundle\Provider\TaxProviderInterface;
use Oro\Bundle\TaxBundle\Provider\TaxProviderRegistry;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class OrderLineItemTypeExtensionTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var TaxationSettingsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $taxationSettingsProvider;

    /** @var TaxProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $taxProvider;

    /** @var AppliedDiscountsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $appliedDiscountsProvider;

    /** @var SectionProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $sectionProvider;

    /** @var LineItemSubtotalProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $lineItemSubtotalProvider;

    /** @var OrderLineItemTypeExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->taxationSettingsProvider = $this->createMock(TaxationSettingsProvider::class);
        $this->taxProvider = $this->createMock(TaxProviderInterface::class);
        $taxProviderRegistry = $this->createMock(TaxProviderRegistry::class);
        $taxProviderRegistry->expects($this->any())
            ->method('getEnabledProvider')
            ->willReturn($this->taxProvider);

        $this->appliedDiscountsProvider = $this->createMock(AppliedDiscountsProvider::class);
        $this->sectionProvider = $this->createMock(SectionProvider::class);
        $this->lineItemSubtotalProvider = $this->createMock(LineItemSubtotalProvider::class);

        $this->extension = new OrderLineItemTypeExtension(
            $this->taxationSettingsProvider,
            $taxProviderRegistry,
            $this->appliedDiscountsProvider,
            $this->sectionProvider,
            $this->lineItemSubtotalProvider
        );
    }

    public function testGetExtendedTypes()
    {
        $this->assertEquals([OrderLineItemType::class], OrderLineItemTypeExtension::getExtendedTypes());
    }

    public function testBuildView()
    {
        $this->sectionProvider->expects($this->once())
            ->method('addSections')
            ->with(
                $this->equalTo(OrderLineItemType::class),
                $this->logicalAnd(
                    $this->isType('array'),
                    $this->arrayHasKey('applied_discounts')
                )
            );

        $view = new FormView();
        $form = $this->createMock(FormInterface::class);

        $this->extension->buildView($view, $form, []);
    }

    /**
     * @dataProvider finishViewDataProvider
     */
    public function testFinishView(array $sourceData, array $expectedData)
    {
        $orderLineItem = $this->getEntity(OrderLineItem::class, ['id' => 1]);
        $orderLineItem->setCurrency($sourceData['currency']);
        $orderLineItem->setValue($sourceData['price']);
        $orderLineItem->setQuantity($sourceData['quantity']);

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('getData')
            ->willReturn($orderLineItem);

        $this->appliedDiscountsProvider->expects($this->once())
            ->method('getDiscountsAmountByLineItem')
            ->with($orderLineItem)
            ->willReturn($sourceData['discountAmount']);

        $this->taxationSettingsProvider->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->taxProvider->expects($this->once())
            ->method('getTax')
            ->with($orderLineItem)
            ->willReturn($sourceData['taxes']);

        $this->lineItemSubtotalProvider->expects($this->once())
            ->method('getRowTotal')
            ->with($orderLineItem)
            ->willReturnCallback(function (OrderLineItem $orderLineItem) {
                return $orderLineItem->getValue() * $orderLineItem->getQuantity();
            });

        $view = new FormView();

        $this->extension->finishView($view, $form, []);

        $this->assertEquals($expectedData, $view->vars['applied_discounts']);
    }

    public function testFinishViewWithoutEntity()
    {
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('getData')
            ->willReturn(null);

        $this->appliedDiscountsProvider->expects($this->never())
            ->method('getDiscountsAmountByLineItem');

        $this->taxationSettingsProvider->expects($this->never())
            ->method('isEnabled');

        $this->taxProvider->expects($this->never())
            ->method('getTax');

        $view = new FormView();

        $this->extension->finishView($view, $form, []);
    }

    /**
     * @dataProvider finishViewWithDisabledTaxesDataProvider
     */
    public function testFinishViewWithDisabledTaxes(array $sourceData, array $expectedData)
    {
        $orderLineItem = $this->getEntity(OrderLineItem::class, ['id' => 1]);
        $orderLineItem->setCurrency($sourceData['currency']);
        $orderLineItem->setValue($sourceData['price']);
        $orderLineItem->setQuantity($sourceData['quantity']);

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('getData')
            ->willReturn($orderLineItem);

        $this->appliedDiscountsProvider->expects($this->once())
            ->method('getDiscountsAmountByLineItem')
            ->with($orderLineItem)
            ->willReturn($sourceData['discountAmount']);

        $this->taxationSettingsProvider->expects($this->once())
            ->method('isEnabled')
            ->willReturn(false);

        $this->taxProvider->expects($this->never())
            ->method('getTax');

        $this->lineItemSubtotalProvider->expects($this->once())
            ->method('getRowTotal')
            ->with($orderLineItem)
            ->willReturnCallback(function (OrderLineItem $orderLineItem) {
                return $orderLineItem->getValue() * $orderLineItem->getQuantity();
            });

        $view = new FormView();

        $this->extension->finishView($view, $form, []);

        $this->assertEquals($expectedData, $view->vars['applied_discounts']);
    }

    public function finishViewDataProvider(): array
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

    public function finishViewWithDisabledTaxesDataProvider(): array
    {
        return [
            [
                'sourceData' => [
                    'currency' => 'USD',
                    'price' => 1.34,
                    'quantity' => 7,
                    'discountAmount' => 3.0,
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
