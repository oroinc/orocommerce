<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Twig;

use Oro\Bundle\CheckoutBundle\Twig\LineItemsExtension;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class LineItemsExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var TotalProcessorProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $totalsProvider;

    /** @var LineItemSubtotalProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $lineItemSubtotalProvider;

    /** @var LocalizationHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $localizedHelper;

    /** @var EntityNameResolver|\PHPUnit\Framework\MockObject\MockObject */
    protected $entityNameResolver;

    /** @var LineItemsExtension */
    protected $extension;

    protected function setUp(): void
    {
        $this->totalsProvider = self::createMock(TotalProcessorProvider::class);
        $this->lineItemSubtotalProvider = self::createMock(LineItemSubtotalProvider::class);
        $this->localizedHelper = self::createMock(LocalizationHelper::class);
        $this->entityNameResolver = self::createMock(EntityNameResolver::class);
        $this->entityNameResolver
            ->method('getName')
            ->willReturnCallback(function ($param) {
                return $param ? 'Item Sku' : 'Item Name';
            });

        $container = self::getContainerBuilder()
            ->add(TotalProcessorProvider::class, $this->totalsProvider)
            ->add(LineItemSubtotalProvider::class, $this->lineItemSubtotalProvider)
            ->add(LocalizationHelper::class, $this->localizedHelper)
            ->add(EntityNameResolver::class, $this->entityNameResolver)
            ->getContainer($this);

        $this->extension = new LineItemsExtension($container);
    }

    public function testGetName()
    {
        self::assertEquals('oro_checkout_order_line_items', LineItemsExtension::NAME);
        self::assertEquals('oro_checkout_order_line_items', $this->extension->getName());
    }

    /**
     * @dataProvider productDataProvider
     * @param boolean $freeForm
     */
    public function testGetOrderLineItems($freeForm)
    {
        $currency = 'UAH';
        $quantity = 22;
        $priceValue = 123;
        $name = 'Item Name';
        $sku = 'Item Sku';
        $comment = 'Comment';
        $shipBy = new \DateTime();

        $subtotals = [
            (new Subtotal())
                ->setLabel('label2')
                ->setAmount(321)
                ->setOperation(Subtotal::OPERATION_SUBTRACTION)
                ->setCurrency('UAH'),
            (new Subtotal())->setLabel('label1')->setAmount(123)->setCurrency('USD')
        ];
        $this->totalsProvider->expects($this->once())->method('getSubtotals')->willReturn($subtotals);
        $this->lineItemSubtotalProvider->expects($this->any())->method('getRowTotal')->willReturn(321);
        $order = new Order();
        $order->setCurrency($currency);

        $product = $freeForm ? null : (new Product())->setSku($sku);
        $order->addLineItem(
            $this->createLineItem(
                $currency,
                $quantity,
                $priceValue,
                $name,
                $sku,
                $comment,
                $shipBy,
                $product
            )
        );

        $total = new Subtotal();
        $totalLabel = 'my total';
        $totalCurrency = 'USD';
        $totalAmount = 777;
        $total->setLabel($totalLabel);
        $total->setAmount($totalAmount);
        $total->setCurrency($totalCurrency);
        $this->totalsProvider->expects($this->once())
            ->method('getTotal')
            ->with($order)
            ->willReturn($total);

        $result = self::callTwigFunction($this->extension, 'order_line_items', [$order]);
        $this->assertArrayHasKey('lineItems', $result);
        $this->assertArrayHasKey('subtotals', $result);
        $this->assertCount(1, $result['lineItems']);
        $this->assertCount(2, $result['subtotals']);

        $lineItem = $result['lineItems'][0];
        $productName = $freeForm ? $name : $sku;
        $this->assertEquals($productName, $lineItem['product_name']);
        $this->assertEquals($sku, $lineItem['product_sku']);
        $this->assertEquals($comment, $lineItem['comment']);
        $this->assertEquals($shipBy, $lineItem['ship_by']);
        $this->assertEquals($quantity, $lineItem['quantity']);
        /** @var Price $price */
        $price = $lineItem['price'];
        $this->assertEquals($priceValue, $price->getValue());
        $this->assertEquals($currency, $price->getCurrency());

        /** @var Price $subtotal */
        $subtotal = $lineItem['subtotal'];
        $this->assertEquals(321, $subtotal->getValue());
        $this->assertEquals('UAH', $subtotal->getCurrency());
        $this->assertNull($lineItem['unit']);

        $firstSubtotal = $result['subtotals'][0];
        $this->assertEquals('label2', $firstSubtotal['label']);
        /** @var Price $totalPrice */
        $totalPrice = $firstSubtotal['totalPrice'];
        $this->assertEquals(-321, $totalPrice->getValue());
        $this->assertEquals('UAH', $totalPrice->getCurrency());

        $total = $result['total'];
        $this->assertEquals($totalLabel, $total['label']);
        /** @var Price $totalPrice */
        $totalPrice = $total['totalPrice'];
        $this->assertEquals($totalAmount, $totalPrice->getValue());
        $this->assertEquals($totalCurrency, $totalPrice->getCurrency());
    }

    /**
     * @return array
     */
    public function productDataProvider()
    {
        return [
            'withoutProduct' => ['freeForm' => true],
            'withProduct' => ['freeForm' => false]
        ];
    }

    /**
     * @param string $currency
     * @param float $quantity
     * @param float $priceValue
     * @param string $name
     * @param string $sku
     * @param string $comment
     * @param \DateTime $shipBy
     * @param Product|null $product
     * @return OrderLineItem
     */
    protected function createLineItem(
        $currency,
        $quantity,
        $priceValue,
        $name,
        $sku,
        $comment,
        $shipBy,
        Product $product = null
    ) {
        $lineItem = new OrderLineItem();
        $lineItem->setCurrency($currency);
        $lineItem->setQuantity($quantity);
        $lineItem->setPrice(Price::create($priceValue, $currency));
        $lineItem->setProductSku($sku);
        $lineItem->setComment($comment);
        $lineItem->setShipBy($shipBy);
        if (!$product) {
            $lineItem->setFreeFormProduct($name);
        } else {
            $lineItem->setProduct($product);
        }

        return $lineItem;
    }
}
