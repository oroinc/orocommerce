<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\EventListener\HasPriceInShoppingLineItemsListener;
use Oro\Bundle\CheckoutBundle\Tests\Unit\Model\Action\CheckoutSourceStub;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaRequestHandler;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Component\Action\Event\ExtendableConditionEvent;
use Oro\Component\Action\Event\ExtendableEventData;
use Oro\Component\Testing\ReflectionUtil;

class HasPriceInShoppingLineItemsListenerTest extends \PHPUnit\Framework\TestCase
{
    private const CURRENCY = 'USD';

    /** @var ProductPriceProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $productPriceProvider;

    /** @var ProductPriceScopeCriteriaRequestHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $scopeCriteriaRequestHandler;

    /** @var ProductPriceCriteriaFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $productPriceCriteriaFactory;

    /** @var HasPriceInShoppingLineItemsListener */
    private $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->productPriceProvider = $this->createMock(ProductPriceProviderInterface::class);
        $this->scopeCriteriaRequestHandler = $this->createMock(ProductPriceScopeCriteriaRequestHandler::class);
        $this->productPriceCriteriaFactory = $this->createMock(ProductPriceCriteriaFactoryInterface::class);

        $this->listener = new HasPriceInShoppingLineItemsListener(
            $this->productPriceProvider,
            $this->scopeCriteriaRequestHandler,
            $this->productPriceCriteriaFactory
        );
    }

    private function getProduct(int $id): Product
    {
        $product = new Product();
        ReflectionUtil::setId($product, $id);

        return $product;
    }

    private function getProductUnit(string $code): ProductUnit
    {
        $productUnit = new ProductUnit();
        $productUnit->setCode($code);

        return $productUnit;
    }

    private function getCheckoutLineItem(
        Product $product,
        ProductUnit $productUnit,
        int $quantity,
        bool $priceFixed
    ): CheckoutLineItem {
        $lineItem = new CheckoutLineItem();
        $lineItem->setProduct($product);
        $lineItem->setProductUnit($productUnit);
        $lineItem->setQuantity($quantity);
        $lineItem->setPriceFixed($priceFixed);

        return $lineItem;
    }

    /**
     * @return Collection<int, CheckoutLineItem>
     */
    private function createCheckoutLineItems(): Collection
    {
        $firstProduct = $this->getProduct(1);
        $firstProductUnit = $this->getProductUnit('item');
        $secondProduct = $this->getProduct(2);
        $secondProductUnit = $this->getProductUnit('item');
        $thirdProduct = $this->getProduct(3);
        $thirdProductUnit = $this->getProductUnit('item');

        return new ArrayCollection([
            $this->getCheckoutLineItem($firstProduct, $firstProductUnit, 1, false),
            $this->getCheckoutLineItem($secondProduct, $secondProductUnit, 2, false),
            $this->getCheckoutLineItem($thirdProduct, $thirdProductUnit, 0, true),
        ]);
    }

    /**
     * @return Collection<int, CheckoutLineItem>
     */
    private function createCheckoutLineItemsWithoutQuantity(): Collection
    {
        $firstProduct = $this->getProduct(1);
        $firstProductUnit = $this->getProductUnit('item');
        $secondProduct = $this->getProduct(2);
        $secondProductUnit = $this->getProductUnit('item');

        return new ArrayCollection([
            $this->getCheckoutLineItem($firstProduct, $firstProductUnit, 0, false),
            $this->getCheckoutLineItem($secondProduct, $secondProductUnit, 0, false),
        ]);
    }

    private function expectsPrepareLineItemsAndReturnPrices(array $prices): ExtendableConditionEvent
    {
        $lineItems = $this->createCheckoutLineItems();

        $checkout = new Checkout();
        $checkout->setLineItems($lineItems);

        $context = new ExtendableEventData(['checkout' => $checkout]);
        $criteria =  $this->createMock(ProductPriceScopeCriteria::class);

        $this->scopeCriteriaRequestHandler->expects($this->once())
            ->method('getPriceScopeCriteria')
            ->willReturn($criteria);

        $productPriceCriteria = $this->createMock(ProductPriceCriteria::class);

        $this->productPriceCriteriaFactory->expects(self::any())
            ->method('createListFromProductLineItems')
            ->willReturn([
                $productPriceCriteria,
                $productPriceCriteria
            ]);

        $productPriceCriteria->expects(self::any())
            ->method('getProduct')
            ->willReturnOnConsecutiveCalls(
                $lineItems[0]->getProduct(),
                $lineItems[1]->getProduct()
            );

        $productPriceCriteria->expects(self::any())
            ->method('getQuantity')
            ->willReturnOnConsecutiveCalls(
                $lineItems[0]->getQuantity(),
                $lineItems[1]->getQuantity()
            );

        $productPriceCriteria->expects(self::any())
            ->method('getProductUnit')
            ->willReturnOnConsecutiveCalls(
                $lineItems[0]->getProductUnit(),
                $lineItems[1]->getProductUnit()
            );

        $productPriceCriteria->expects(self::any())
            ->method('getCurrency')
            ->willReturn(self::CURRENCY);

        $this->productPriceProvider->expects($this->once())
            ->method('getMatchedPrices')
            ->with(
                $this->callback(function ($productsPricesCriteria) use ($lineItems) {
                    /** @var ProductPriceCriteria[] $productsPricesCriteria */
                    $this->assertCount(2, $productsPricesCriteria);
                    $this->assertEquals($lineItems[0]->getProduct(), $productsPricesCriteria[0]->getProduct());
                    $this->assertEquals($lineItems[1]->getProduct(), $productsPricesCriteria[1]->getProduct());
                    $this->assertEquals($lineItems[0]->getProductUnit(), $productsPricesCriteria[0]->getProductUnit());
                    $this->assertEquals($lineItems[1]->getProductUnit(), $productsPricesCriteria[1]->getProductUnit());
                    $this->assertEquals($lineItems[0]->getQuantity(), $productsPricesCriteria[0]->getQuantity());
                    $this->assertEquals($lineItems[1]->getQuantity(), $productsPricesCriteria[1]->getQuantity());
                    $this->assertEquals(self::CURRENCY, $productsPricesCriteria[0]->getCurrency());
                    $this->assertEquals(self::CURRENCY, $productsPricesCriteria[1]->getCurrency());

                    return true;
                }),
                $criteria
            )
            ->willReturn($prices);

        return new ExtendableConditionEvent($context);
    }

    private function expectsPrepareLineItemsWithoutQuantity(): ExtendableConditionEvent
    {
        $lineItems = $this->createCheckoutLineItemsWithoutQuantity();

        $checkout = new Checkout();
        $checkout->setLineItems($lineItems);

        $context = new ActionData(['checkout' => $checkout]);

        $this->scopeCriteriaRequestHandler->expects($this->never())
            ->method('getPriceScopeCriteria');

        $this->productPriceProvider->expects($this->never())
            ->method('getMatchedPrices');

        return new ExtendableConditionEvent($context);
    }

    public function testOnStartCheckoutConditionCheckWhenCheckoutHasNoPrices()
    {
        $event = $this->expectsPrepareLineItemsAndReturnPrices([]);

        $this->listener->onStartCheckoutConditionCheck($event);

        $this->assertNotEmpty($event->getErrors());
        $expectedErrors = new ArrayCollection([
            [
                'message' => 'oro.frontend.shoppinglist.messages.cannot_create_order_no_line_item_with_price',
                'context' => null
            ]
        ]);

        $this->assertEquals($expectedErrors, $event->getErrors());
    }

    public function testOnStartCheckoutConditionCheckWhenCheckoutHasAtLeastOnePrice()
    {
        $event = $this->expectsPrepareLineItemsAndReturnPrices([new Price()]);

        $this->listener->onStartCheckoutConditionCheck($event);

        $this->assertEmpty($event->getErrors());
    }

    public function testOnStartCheckoutConditionCheckWhenCheckoutHasNoItems()
    {
        $shoppingList = new ShoppingList();
        $checkoutSource = new CheckoutSourceStub();
        $checkoutSource->setShoppingList($shoppingList);
        $checkout = new Checkout();
        $checkout->setSource($checkoutSource);

        $context = new ActionData(['checkout' => $checkout]);
        $event = new ExtendableConditionEvent($context);

        $this->listener->onStartCheckoutConditionCheck($event);

        $this->assertEmpty($event->getErrors());
    }

    public function testOnStartCheckoutConditionCheckWhenCheckoutHasItemsWithoutQuantity()
    {
        $event = $this->expectsPrepareLineItemsWithoutQuantity();

        $this->listener->onStartCheckoutConditionCheck($event);

        $expectedErrors = new ArrayCollection([
            [
                'message' => 'oro.frontend.shoppinglist.messages.cannot_create_order_no_line_item_with_quantity',
                'context' => null
            ]
        ]);

        $this->assertEquals($expectedErrors, $event->getErrors());
    }
}
