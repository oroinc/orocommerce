<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Twig;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Twig\LineItemsExtension;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Entity\OrderProductKitItemLineItem;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductKitItemLabel;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Layout\DataProvider\ConfigurableProductProvider;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LineItemsExtensionTest extends TestCase
{
    use TwigExtensionTestCaseTrait;

    private TotalProcessorProvider|MockObject $totalsProvider;

    private LineItemSubtotalProvider|MockObject $lineItemSubtotalProvider;

    private LocalizationHelper|MockObject $localizedHelper;

    private LineItemsExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->totalsProvider = $this->createMock(TotalProcessorProvider::class);
        $this->lineItemSubtotalProvider = $this->createMock(LineItemSubtotalProvider::class);
        $this->localizedHelper = $this->createMock(LocalizationHelper::class);
        $entityNameResolver = $this->createMock(EntityNameResolver::class);
        $configurableProductProvider = $this->createMock(ConfigurableProductProvider::class);
        $entityNameResolver->expects(self::any())
            ->method('getName')
            ->willReturnCallback(function (?Product $entity) {
                if ($entity) {
                    return $entity->getDefaultName() ?? 'Item Sku';
                } else {
                    return 'Item Name';
                }
            });

        $container = self::getContainerBuilder()
            ->add(TotalProcessorProvider::class, $this->totalsProvider)
            ->add(LineItemSubtotalProvider::class, $this->lineItemSubtotalProvider)
            ->add(LocalizationHelper::class, $this->localizedHelper)
            ->add(EntityNameResolver::class, $entityNameResolver)
            ->add('oro_product.layout.data_provider.configurable_products', $configurableProductProvider)
            ->getContainer($this);

        $this->extension = new LineItemsExtension($container);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @dataProvider productDataProvider
     */
    public function testGetOrderLineItems(bool $freeForm): void
    {
        $currency = 'UAH';
        $quantity = 22;
        $priceValue = 123;
        $name = 'Item Name';
        $sku = 'Item Sku';
        $comment = 'Comment';
        $shipBy = new \DateTime();

        $organization = (new Organization())->setName('Oro');
        $subtotals = new ArrayCollection([
            (new Subtotal())
                ->setLabel('label2')
                ->setAmount(321)
                ->setOperation(Subtotal::OPERATION_SUBTRACTION)
                ->setCurrency('UAH'),
            (new Subtotal())->setLabel('label1')->setAmount(123)->setCurrency('USD')
        ]);
        $this->totalsProvider->expects(self::once())
            ->method('getSubtotals')
            ->willReturn($subtotals);
        $this->lineItemSubtotalProvider->expects(self::any())
            ->method('getRowTotal')
            ->willReturn(321);
        $order = new Order();
        $order->setCurrency($currency);

        $product = $freeForm ? null : (new Product())->setSku($sku)->setOrganization($organization);
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

        $productKitName = 'Product Kit';
        $productKit = (new Product())->setId(2)
            ->setType(Product::TYPE_KIT)
            ->setDefaultName($productKitName)
            ->setOrganization($organization);
        $productKitUnit = (new ProductUnit())->setCode('item');

        $kitItemProductName = 'Product 3';
        $kitItemProduct = (new Product())->setId(3)->setDefaultName($kitItemProductName);

        $kitItemLabel = 'Kit Item Label';
        $kitItemLabels = [(new ProductKitItemLabel())->setString($kitItemLabel)];
        $kitItem = (new ProductKitItemStub(1))->setLabels($kitItemLabels);
        $kitItemLineItemPrice = Price::create(13, $currency);
        $kitItemLineItem = $this->createKitItemLineItem(
            1,
            $productKitUnit,
            $kitItemLineItemPrice,
            $kitItemProduct,
            $kitItem
        );
        $productKitSku = 'productKitSku';
        $kitLineItem = $this->createLineItem(
            $currency,
            $quantity,
            $priceValue,
            '',
            $productKitSku,
            '',
            $shipBy,
            $productKit
        );
        $kitLineItem->addKitItemLineItem($kitItemLineItem);

        $order->addLineItem($kitLineItem);

        $total = new Subtotal();
        $totalLabel = 'my total';
        $totalCurrency = 'USD';
        $totalAmount = 777;
        $total->setLabel($totalLabel);
        $total->setAmount($totalAmount);
        $total->setCurrency($totalCurrency);
        $this->totalsProvider->expects(self::once())
            ->method('getTotal')
            ->with($order)
            ->willReturn($total);

        $this->localizedHelper->expects(self::once())
            ->method('getLocalizedValue')
            ->with(new ArrayCollection($kitItemLabels))
            ->willReturn('Kit Item Label');

        $result = self::callTwigFunction($this->extension, 'order_line_items', [$order]);
        self::assertArrayHasKey('lineItems', $result);
        self::assertArrayHasKey('subtotals', $result);
        self::assertCount(2, $result['lineItems']);
        self::assertCount(2, $result['subtotals']);

        // Check simple line item
        $lineItem = $result['lineItems'][0];
        $productName = $freeForm ? $name : $sku;
        self::assertEquals($productName, $lineItem['product_name']);
        self::assertEquals($sku, $lineItem['product_sku']);
        self::assertEquals($comment, $lineItem['comment']);
        self::assertEquals($shipBy, $lineItem['ship_by']);
        self::assertEquals($quantity, $lineItem['quantity']);
        self::assertEmpty($lineItem['kitItemLineItems']);
        /** @var Price $price */
        $price = $lineItem['price'];
        self::assertEquals($priceValue, $price->getValue());
        self::assertEquals($currency, $price->getCurrency());

        // Check product kit line item
        $lineItem = $result['lineItems'][1];
        self::assertEquals($productKitName, $lineItem['product_name']);
        self::assertEquals($productKitSku, $lineItem['product_sku']);
        self::assertEquals('', $lineItem['comment']);
        self::assertEquals($shipBy, $lineItem['ship_by']);
        self::assertEquals($quantity, $lineItem['quantity']);
        /** @var Price $price */
        $price = $lineItem['price'];
        self::assertEquals($priceValue, $price->getValue());
        self::assertEquals($currency, $price->getCurrency());
        $kitItemLineItemsResult = $lineItem['kitItemLineItems'];
        self::assertCount(1, $kitItemLineItemsResult);
        $kitItemLineItemResult = $kitItemLineItemsResult[0];
        self::assertEquals($kitItemLabel, $kitItemLineItemResult['kitItemLabel']);
        self::assertEquals($productKitUnit, $kitItemLineItemResult['unit']);
        self::assertEquals(1, $kitItemLineItemResult['quantity']);
        self::assertEquals($kitItemLineItemPrice, $kitItemLineItemResult['price']);
        self::assertEquals($kitItemProduct, $kitItemLineItemResult['productName']);

        /** @var Price $subtotal */
        $subtotal = $lineItem['subtotal'];
        self::assertEquals(321, $subtotal->getValue());
        self::assertEquals('UAH', $subtotal->getCurrency());
        self::assertNull($lineItem['unit']);

        $firstSubtotal = $result['subtotals'][0];
        self::assertEquals('label2', $firstSubtotal['label']);
        /** @var Price $totalPrice */
        $totalPrice = $firstSubtotal['totalPrice'];
        self::assertEquals(-321, $totalPrice->getValue());
        self::assertEquals('UAH', $totalPrice->getCurrency());

        $total = $result['total'];
        self::assertEquals($totalLabel, $total['label']);
        /** @var Price $totalPrice */
        $totalPrice = $total['totalPrice'];
        self::assertEquals($totalAmount, $totalPrice->getValue());
        self::assertEquals($totalCurrency, $totalPrice->getCurrency());
    }

    public function productDataProvider(): array
    {
        return [
            'withoutProduct' => ['freeForm' => true],
            'withProduct' => ['freeForm' => false]
        ];
    }

    private function createLineItem(
        string $currency,
        float $quantity,
        float $priceValue,
        string $name,
        string $sku,
        string $comment,
        \DateTime $shipBy,
        ?Product $product = null
    ): OrderLineItem {
        $lineItem = new OrderLineItem();
        $lineItem->setCurrency($currency);
        $lineItem->setQuantity($quantity);
        $lineItem->setPrice(Price::create($priceValue, $currency));
        $lineItem->setProductSku($sku);
        $lineItem->setComment($comment);
        $lineItem->setShipBy($shipBy);
        if ($product) {
            $lineItem->setProduct($product);
        } else {
            $lineItem->setFreeFormProduct($name);
        }

        return $lineItem;
    }

    private function createKitItemLineItem(
        float $quantity,
        ?ProductUnit $productUnit,
        ?Price $price,
        ?Product $product,
        ?ProductKitItem $kitItem
    ): OrderProductKitItemLineItem {
        return (new OrderProductKitItemLineItem())
            ->setProduct($product)
            ->setKitItem($kitItem)
            ->setProductUnit($productUnit)
            ->setQuantity($quantity)
            ->setPrice($price)
            ->setSortOrder(1);
    }
}
