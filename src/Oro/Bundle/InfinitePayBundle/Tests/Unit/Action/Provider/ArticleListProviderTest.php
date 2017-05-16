<?php

namespace Oro\Bundle\InfinitePayBundle\Tests\Unit\Action\Provider;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\InfinitePayBundle\Action\Provider\ArticleListProvider;
use Oro\Bundle\InfinitePayBundle\Action\Provider\ArticleListProviderInterface;
use Oro\Bundle\InfinitePayBundle\Action\Provider\InvoiceTotalsProvider;
use Oro\Bundle\InfinitePayBundle\Action\Provider\InvoiceTotalsProviderInterface;
use Oro\Bundle\InfinitePayBundle\Service\InfinitePay\OrderArticle;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Model\ResultElement;
use Oro\Bundle\TaxBundle\Model\TaxResultElement;

/**
 * {@inheritdoc}
 */
class ArticleListProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var InvoiceTotalsProviderInterface
     */
    protected $invoiceTotalsProvider;

    /** @var ArticleListProviderInterface */
    protected $articleListProvider;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->invoiceTotalsProvider = $this
            ->getMockBuilder(InvoiceTotalsProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $taxArray = $this->getTaxArray();
        $this->invoiceTotalsProvider->method('getTax')->willReturn($taxArray);
        $this->articleListProvider = new ArticleListProvider($this->invoiceTotalsProvider);
    }

    public function testGetArticleList()
    {
        $order = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();
        $order->method('getLineItems')->willReturn(new ArrayCollection([
            $this->getLineItem('1GB82', 'Women’s Slip-On Clog', '1998.9999999999998', 2),
            $this->getLineItem('0RT28', '220 Lumen Rechargeable Headlamp', '9999.0', 1),
        ]));

        /** @var OrderArticle[] $list */
        $list = $this->articleListProvider->getArticleList($order)->getARTICLE();

        $this->assertEquals(237880, $list[0]->getArticlePriceGross());
        $this->assertEquals(199900, $list[0]->getArticlePriceNet());
        $this->assertEquals(1900, $list[0]->getArticleVatPerc());

        $this->assertEquals(1189881, $list[1]->getArticlePriceGross());
        $this->assertEquals(999900, $list[1]->getArticlePriceNet());
    }

    /**
     * @param string $sku
     * @param string $name
     * @param float  $priceNet
     * @param int    $quantity
     *
     * @return OrderLineItem
     */
    private function getLineItem($sku, $name, $priceNet, $quantity)
    {
        $item = new OrderLineItem();

        $product = new Product();
        $product->addName((new LocalizedFallbackValue())->setString($name));
        $product->setSku($sku);
        $item->setProduct($product);
        $item->setPrice((new Price())->setValue($priceNet));
        $item->setQuantity($quantity);

        return $item;
    }

    private function getTaxArray()
    {
        $taxTotal = new ResultElement();
        $taxTotal->offsetSet('excludingTax', 12.34);
        $taxShipping = new ResultElement();
        $taxShipping->offsetSet('excludingTax', 8.40);
        $taxShipping->offsetSet('includingTax', 10.0);
        $taxTaxes = new ResultElement();

        $grossPrice1 = new ResultElement();
        $grossPrice1->offsetSet('includingTax', 2378.80);
        $vatRate1 = new TaxResultElement();
        $vatRate1->offsetSet(TaxResultElement::RATE, 0.19);

        $productTax1 = new Result();
        $productTax1->offsetSet(Result::UNIT, $grossPrice1);
        $productTax1->offsetSet(Result::TAXES, [$vatRate1]);

        $grossPrice2 = new ResultElement();
        $grossPrice2->offsetSet('includingTax', 11898.81);
        $productTax2 = new Result();
        $productTax2->offsetSet(Result::UNIT, $grossPrice2);

        $taxItems = [$productTax1, $productTax2];
        $taxData = [
            'total' => $taxTotal,
            'taxes' => $taxTaxes,
            'shipping' => $taxShipping,
            'items' => $taxItems,
        ];
        $tax = [
            'type' => 'tax',
            'label' => 'Tax',
            'amount' => '12.59',
            'currency' => 'USD',
            'visible' => true,
            'data' => $taxData,
        ];

        return $tax;
    }
}
