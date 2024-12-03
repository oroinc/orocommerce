<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Workflow\ActionGroup;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Action\DefaultShippingMethodSetterInterface;
use Oro\Bundle\CheckoutBundle\Action\MultiShipping\DefaultMultiShippingGroupMethodSetterInterface;
use Oro\Bundle\CheckoutBundle\Action\MultiShipping\DefaultMultiShippingMethodSetterInterface;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Manager\MultiShipping\CheckoutLineItemGroupsShippingManagerInterface;
use Oro\Bundle\CheckoutBundle\Manager\MultiShipping\CheckoutLineItemsShippingManagerInterface;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\ConfigProvider;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\ShippingMethodActions;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\UpdateShippingPriceInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ShippingMethodActionsTest extends TestCase
{
    private ConfigProvider|MockObject $configProvider;
    private ActionExecutor|MockObject $actionExecutor;
    private DefaultShippingMethodSetterInterface|MockObject $defaultShippingMethodSetter;
    private DefaultMultiShippingMethodSetterInterface|MockObject $defaultMultiShippingMethodSetter;
    private DefaultMultiShippingGroupMethodSetterInterface|MockObject $defaultMultiShippingGroupMethodSetter;
    private CheckoutLineItemsShippingManagerInterface|MockObject $checkoutLineItemsShipping;
    private CheckoutLineItemGroupsShippingManagerInterface|MockObject $checkoutLineItemGroupsShipping;
    private UpdateShippingPriceInterface|MockObject $updateShippingPrice;
    private ShippingMethodActions $shippingMethodActions;

    #[\Override]
    protected function setUp(): void
    {
        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->actionExecutor = $this->createMock(ActionExecutor::class);
        $this->defaultShippingMethodSetter = $this->createMock(DefaultShippingMethodSetterInterface::class);
        $this->defaultMultiShippingMethodSetter = $this->createMock(DefaultMultiShippingMethodSetterInterface::class);
        $this->defaultMultiShippingGroupMethodSetter = $this->createMock(
            DefaultMultiShippingGroupMethodSetterInterface::class
        );
        $this->checkoutLineItemsShipping = $this->createMock(CheckoutLineItemsShippingManagerInterface::class);
        $this->checkoutLineItemGroupsShipping = $this->createMock(
            CheckoutLineItemGroupsShippingManagerInterface::class
        );
        $this->updateShippingPrice = $this->createMock(UpdateShippingPriceInterface::class);

        $this->shippingMethodActions = new ShippingMethodActions(
            $this->actionExecutor,
            $this->configProvider,
            $this->defaultShippingMethodSetter,
            $this->defaultMultiShippingMethodSetter,
            $this->defaultMultiShippingGroupMethodSetter,
            $this->checkoutLineItemsShipping,
            $this->checkoutLineItemGroupsShipping,
            $this->updateShippingPrice
        );
    }

    public function testHasApplicableShippingRulesHasEnabledShippingRules(): void
    {
        $errors = new ArrayCollection();
        $checkout = new Checkout();
        $checkout->setShippingMethod('flat_rate');

        $this->configProvider->expects($this->any())
            ->method('isMultiShippingEnabled')
            ->willReturn(false);

        $this->actionExecutor->expects($this->any())
            ->method('evaluateExpression')
            ->with(
                'shipping_method_has_enabled_shipping_rules',
                ['method_identifier' => 'flat_rate'],
                $errors,
                'oro.checkout.workflow.condition.shipping_method_is_not_available.message'
            )
            ->willReturn(true);

        $result = $this->shippingMethodActions->hasApplicableShippingRules($checkout, $errors);

        $this->assertTrue($result);
    }

    public function testHasApplicableShippingRulesHasEnabledShippingRulesForMultiShippingPerLineItem(): void
    {
        $errors = new ArrayCollection();
        $checkout = new Checkout();
        $checkout->setShippingMethod('flat_rate');

        $this->configProvider->expects($this->any())
            ->method('isMultiShippingEnabled')
            ->willReturn(true);
        $this->configProvider->expects($this->any())
            ->method('isShippingSelectionByLineItemEnabled')
            ->willReturn(true);

        $this->actionExecutor->expects($this->any())
            ->method('evaluateExpression')
            ->with(
                'line_items_shipping_methods_has_enabled_shipping_rules',
                ['entity' => $checkout],
                $errors,
                'oro.checkout.workflow.condition.shipping_method_is_not_available.message'
            )
            ->willReturn(true);

        $result = $this->shippingMethodActions->hasApplicableShippingRules($checkout, $errors);

        $this->assertTrue($result);
    }

    public function testHasApplicableShippingRulesHasEnabledShippingRulesForMultiShippingPerLineItemGroup(): void
    {
        $errors = new ArrayCollection();
        $checkout = new Checkout();
        $checkout->setShippingMethod('flat_rate');

        $this->configProvider->expects($this->any())
            ->method('isMultiShippingEnabled')
            ->willReturn(true);
        $this->configProvider->expects($this->any())
            ->method('isShippingSelectionByLineItemEnabled')
            ->willReturn(false);
        $this->configProvider->expects($this->any())
            ->method('isLineItemsGroupingEnabled')
            ->willReturn(true);

        $this->actionExecutor->expects($this->any())
            ->method('evaluateExpression')
            ->with(
                'line_item_groups_shipping_methods_has_enabled_shipping_rules',
                ['entity' => $checkout],
                $errors,
                'oro.checkout.workflow.condition.shipping_method_is_not_available.message'
            )
            ->willReturn(true);

        $result = $this->shippingMethodActions->hasApplicableShippingRules($checkout, $errors);

        $this->assertTrue($result);
    }

    public function testUpdateDefaultShippingMethodsWithoutMultiShipping(): void
    {
        $checkout = $this->createMock(Checkout::class);

        $this->configProvider->expects($this->once())
            ->method('isMultiShippingEnabled')
            ->willReturn(false);
        $this->configProvider->expects($this->once())
            ->method('isShippingSelectionByLineItemEnabled')
            ->willReturn(false);
        $this->configProvider->expects($this->once())
            ->method('isLineItemsGroupingEnabled')
            ->willReturn(false);

        $this->defaultShippingMethodSetter->expects($this->once())
            ->method('setDefaultShippingMethod')
            ->with($checkout);
        $this->defaultMultiShippingMethodSetter->expects($this->never())
            ->method('setDefaultShippingMethods');
        $this->defaultMultiShippingGroupMethodSetter->expects($this->never())
            ->method('setDefaultShippingMethods');

        $this->shippingMethodActions->updateDefaultShippingMethods($checkout, [], []);
    }

    public function testUpdateDefaultShippingMethodsWithMultiShippingPerLineItem(): void
    {
        $checkout = $this->createMock(Checkout::class);
        $lineItemsShippingMethods = ['flat_rate_1'];
        $lineItemGroupsShippingMethods = ['flat_rate_2'];

        $this->configProvider->expects($this->once())
            ->method('isMultiShippingEnabled')
            ->willReturn(true);
        $this->configProvider->expects($this->once())
            ->method('isShippingSelectionByLineItemEnabled')
            ->willReturn(true);
        $this->configProvider->expects($this->once())
            ->method('isLineItemsGroupingEnabled')
            ->willReturn(false);

        $this->defaultShippingMethodSetter->expects($this->never())
            ->method('setDefaultShippingMethod');

        $this->defaultMultiShippingMethodSetter->expects($this->once())
            ->method('setDefaultShippingMethods')
            ->with($checkout, $lineItemsShippingMethods, true);
        $this->defaultMultiShippingGroupMethodSetter->expects($this->never())
            ->method('setDefaultShippingMethods');

        $this->shippingMethodActions->updateDefaultShippingMethods(
            $checkout,
            $lineItemsShippingMethods,
            $lineItemGroupsShippingMethods
        );
    }

    public function testUpdateDefaultShippingMethodsWithMultiShippingPerLineItemGroup(): void
    {
        $checkout = $this->createMock(Checkout::class);
        $lineItemsShippingMethods = ['flat_rate_1'];
        $lineItemGroupsShippingMethods = ['flat_rate_2'];

        $this->configProvider->expects($this->once())
            ->method('isMultiShippingEnabled')
            ->willReturn(true);
        $this->configProvider->expects($this->exactly(2))
            ->method('isShippingSelectionByLineItemEnabled')
            ->willReturn(false);
        $this->configProvider->expects($this->once())
            ->method('isLineItemsGroupingEnabled')
            ->willReturn(true);

        $this->defaultShippingMethodSetter->expects($this->never())
            ->method('setDefaultShippingMethod');

        $this->defaultMultiShippingMethodSetter->expects($this->never())
            ->method('setDefaultShippingMethods');
        $this->defaultMultiShippingGroupMethodSetter->expects($this->once())
            ->method('setDefaultShippingMethods')
            ->with($checkout, $lineItemGroupsShippingMethods, true);

        $this->shippingMethodActions->updateDefaultShippingMethods(
            $checkout,
            $lineItemsShippingMethods,
            $lineItemGroupsShippingMethods
        );
    }

    public function testUpdateCheckoutShippingPricesNoMultiShipping(): void
    {
        $checkout = $this->createMock(Checkout::class);

        $this->configProvider->expects($this->once())
            ->method('isShippingSelectionByLineItemEnabled')
            ->willReturn(false);
        $this->configProvider->expects($this->once())
            ->method('isLineItemsGroupingEnabled')
            ->willReturn(false);

        $this->checkoutLineItemsShipping->expects($this->never())
            ->method('updateLineItemsShippingPrices');
        $this->checkoutLineItemGroupsShipping->expects($this->never())
            ->method('updateLineItemGroupsShippingPrices');

        $this->updateShippingPrice->expects($this->once())
            ->method('execute')
            ->with($checkout);

        $this->shippingMethodActions->updateCheckoutShippingPrices($checkout);
    }

    public function testUpdateCheckoutShippingPricesShippingSelectionByLineItemEnabled(): void
    {
        $checkout = $this->createMock(Checkout::class);

        $this->configProvider->expects($this->once())
            ->method('isShippingSelectionByLineItemEnabled')
            ->willReturn(true);
        $this->configProvider->expects($this->once())
            ->method('isLineItemsGroupingEnabled')
            ->willReturn(false);

        $this->checkoutLineItemsShipping->expects($this->once())
            ->method('updateLineItemsShippingPrices')
            ->with($checkout);
        $this->checkoutLineItemGroupsShipping->expects($this->never())
            ->method('updateLineItemGroupsShippingPrices');

        $this->updateShippingPrice->expects($this->once())
            ->method('execute')
            ->with($checkout);

        $this->shippingMethodActions->updateCheckoutShippingPrices($checkout);
    }

    public function testUpdateCheckoutShippingPricesMultiShippingEnabledPerLineItemGroup(): void
    {
        $checkout = $this->createMock(Checkout::class);

        $this->configProvider->expects($this->exactly(2))
            ->method('isShippingSelectionByLineItemEnabled')
            ->willReturn(false);
        $this->configProvider->expects($this->once())
            ->method('isLineItemsGroupingEnabled')
            ->willReturn(true);

        $this->checkoutLineItemsShipping->expects($this->never())
            ->method('updateLineItemsShippingPrices');

        $this->checkoutLineItemGroupsShipping->expects($this->once())
            ->method('updateLineItemGroupsShippingPrices')
            ->with($checkout);

        $this->updateShippingPrice->expects($this->once())
            ->method('execute')
            ->with($checkout);

        $this->shippingMethodActions->updateCheckoutShippingPrices($checkout);
    }

    public function testActualizeShippingMethodsForLineItemsLineItemsShippingMethodsUpdateRequired(): void
    {
        $lineItems = new ArrayCollection([$this->createMock(CheckoutLineItem::class)]);
        $checkout = $this->createMock(Checkout::class);
        $checkout->expects($this->any())
            ->method('getLineItems')
            ->willReturn($lineItems);

        $lineItemsShippingMethods = ['flat_rate_1'];
        $lineItemGroupsShippingMethods = ['flat_rate_2'];

        $this->configProvider->expects($this->once())
            ->method('isShippingSelectionByLineItemEnabled')
            ->willReturn(true);
        $this->configProvider->expects($this->once())
            ->method('isLineItemsGroupingEnabled')
            ->willReturn(false);

        $this->actionExecutor->expects($this->once())
            ->method('evaluateExpression')
            ->with(
                'is_line_items_shipping_methods_update_required',
                [$lineItems, $lineItemsShippingMethods]
            )
            ->willReturn(true);

        $this->defaultMultiShippingMethodSetter->expects($this->once())
            ->method('setDefaultShippingMethods')
            ->with($checkout, $lineItemsShippingMethods);
        $this->defaultMultiShippingGroupMethodSetter->expects($this->never())
            ->method('setDefaultShippingMethods');

        $this->shippingMethodActions->actualizeShippingMethods(
            $checkout,
            $lineItemsShippingMethods,
            $lineItemGroupsShippingMethods
        );
    }

    public function testActualizeShippingMethodsForisLineItemGroupsShippingMethodsUpdateRequired(): void
    {
        $lineItems = new ArrayCollection([$this->createMock(CheckoutLineItem::class)]);
        $checkout = $this->createMock(Checkout::class);
        $checkout->expects($this->any())
            ->method('getLineItems')
            ->willReturn($lineItems);

        $lineItemsShippingMethods = ['flat_rate_1'];
        $lineItemGroupsShippingMethods = ['flat_rate_2'];

        $this->configProvider->expects($this->exactly(2))
            ->method('isShippingSelectionByLineItemEnabled')
            ->willReturn(false);
        $this->configProvider->expects($this->once())
            ->method('isLineItemsGroupingEnabled')
            ->willReturn(true);

        $this->actionExecutor->expects($this->once())
            ->method('evaluateExpression')
            ->with(
                'is_line_item_groups_shipping_methods_update_required',
                [$lineItems, $lineItemGroupsShippingMethods]
            )
            ->willReturn(true);

        $this->defaultMultiShippingMethodSetter->expects($this->never())
            ->method('setDefaultShippingMethods');
        $this->defaultMultiShippingGroupMethodSetter->expects($this->once())
            ->method('setDefaultShippingMethods')
            ->with($checkout, $lineItemGroupsShippingMethods);

        $this->shippingMethodActions->actualizeShippingMethods(
            $checkout,
            $lineItemsShippingMethods,
            $lineItemGroupsShippingMethods
        );
    }
}
