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
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaRequestHandler;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Component\Action\Event\ExtendableConditionEvent;
use Oro\Component\Testing\ReflectionUtil;

class HasPriceInShoppingLineItemsListenerTest extends \PHPUnit\Framework\TestCase
{
    private const CURRENCY = 'USD';

    /** @var ProductPriceProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $productPriceProvider;

    /** @var UserCurrencyManager|\PHPUnit\Framework\MockObject\MockObject */
    private $userCurrencyManager;

    /** @var ProductPriceScopeCriteriaRequestHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $scopeCriteriaRequestHandler;

    /** @var ProductPriceCriteriaFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $productPriceCriteriaFactory;

    /** @var HasPriceInShoppingLineItemsListener */
    private $listener;

    protected function setUp(): void
    {
        $this->productPriceProvider = $this->createMock(ProductPriceProviderInterface::class);
        $this->userCurrencyManager = $this->createMock(UserCurrencyManager::class);
        $this->scopeCriteriaRequestHandler = $this->createMock(ProductPriceScopeCriteriaRequestHandler::class);
        $this->productPriceCriteriaFactory = $this->createMock(ProductPriceCriteriaFactoryInterface::class);

        $this->listener = new HasPriceInShoppingLineItemsListener(
            $this->productPriceProvider,
            $this->userCurrencyManager,
            $this->scopeCriteriaRequestHandler
        );
        $this->listener->setProductPriceCriteriaFactory($this->productPriceCriteriaFactory);
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

        $context = new ActionData(['checkout' => $checkout]);

        $this->userCurrencyManager->expects($this->once())
            ->method('getUserCurrency')
            ->willReturn(self::CURRENCY);

        $criteria = new ProductPriceScopeCriteria();
        $this->scopeCriteriaRequestHandler->expects($this->once())
            ->method('getPriceScopeCriteria')
            ->willReturn($criteria);

        $productsPricesCriteria = [
            new ProductPriceCriteria(
                $lineItems[0]->getProduct(),
                $lineItems[0]->getProductUnit(),
                $lineItems[0]->getQuantity(),
                self::CURRENCY
            ),
            new ProductPriceCriteria(
                $lineItems[1]->getProduct(),
                $lineItems[1]->getProductUnit(),
                $lineItems[1]->getQuantity(),
                self::CURRENCY
            ),
        ];

        $this->productPriceCriteriaFactory->expects(self::once())
            ->method('createListFromProductLineItems')
            ->with(new ArrayCollection([$lineItems[0],$lineItems[1]]), self::CURRENCY)
            ->willReturn($productsPricesCriteria);

        $this->productPriceProvider->expects($this->once())
            ->method('getMatchedPrices')
            ->with($productsPricesCriteria, $criteria)
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

    public function testOnStartCheckoutConditionCheckWhenContextIsNotActionData()
    {
        $event = new ExtendableConditionEvent(new \stdClass());

        $this->listener->onStartCheckoutConditionCheck($event);
    }

    public function testOnStartCheckoutConditionCheckWhenCheckoutIsNotOfCheckoutType()
    {
        $event = new ExtendableConditionEvent(new ActionData(['checkout' => new \stdClass()]));

        $this->listener->onStartCheckoutConditionCheck($event);
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
