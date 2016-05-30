<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Provider;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\PricingBundle\Entity\BasePriceList;
use OroB2B\Bundle\PricingBundle\Model\ProductPriceCriteria;
use OroB2B\Bundle\PricingBundle\Provider\MatchingPriceProvider;
use OroB2B\Bundle\PricingBundle\Provider\ProductPriceProvider;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

class MatchingPriceProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    const PRODUCT_CLASS = 'ProductClass';
    const PRODUCT_UNIT_CLASS = 'ProductUnitClass';

    /** @var ProductPriceProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $productPriceProvider;

    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var MatchingPriceProvider */
    protected $provider;

    protected function setUp()
    {
        $this->productPriceProvider = $this
            ->getMockBuilder('\OroB2B\Bundle\PricingBundle\Provider\ProductPriceProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this
            ->getMockBuilder('\Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new MatchingPriceProvider(
            $this->productPriceProvider,
            $this->doctrineHelper,
            self::PRODUCT_CLASS,
            self::PRODUCT_UNIT_CLASS
        );
    }

    protected function tearDown()
    {
        unset($this->provider, $this->doctrineHelper, $this->productPriceProvider);
    }

    public function testGetMatchingPrices()
    {
        $productId = 1;
        $productUnitCode = 'unitCode';
        $qty = 5.5;
        $currency = 'USD';

        $lineItems = [
            [
                'product' => $productId,
                'unit' => $productUnitCode,
                'qty' => $qty,
                'currency' => $currency,
            ]
        ];

        /** @var Product $product */
        $product = $this->getEntity('\OroB2B\Bundle\ProductBundle\Entity\Product', ['id' => $productId]);
        /** @var BasePriceList $priceList */
        $priceList = $this->getEntity('\OroB2B\Bundle\PricingBundle\Entity\BasePriceList', ['id' => 2]);

        /** @var ProductUnit $productUnit */
        $productUnit = $this->getEntity(
            '\OroB2B\Bundle\ProductBundle\Entity\ProductUnit',
            ['code' => $productUnitCode]
        );

        $this->doctrineHelper->expects($this->exactly(2))
            ->method('getEntityReference')
            ->withConsecutive(
                [self::PRODUCT_CLASS, $productId],
                [self::PRODUCT_UNIT_CLASS, $productUnitCode]
            )
            ->willReturnOnConsecutiveCalls($product, $productUnit);

        $expectedMatchedPrices = [
            'price1' => Price::create(10, 'USD'),
            'price2' => Price::create(15, 'USD'),
            'price3' => Price::create(20, 'EUR'),
        ];
        $this->productPriceProvider->expects($this->once())
            ->method('getMatchedPrices')
            ->with([new ProductPriceCriteria($product, $productUnit, $qty, $currency)], $priceList)
            ->willReturn($expectedMatchedPrices);

        $this->assertEquals(
            $this->formatPrices($expectedMatchedPrices),
            $this->provider->getMatchingPrices($lineItems, $priceList)
        );
    }

    /**
     * @param Price[] $prices
     * @return array
     */
    protected function formatPrices(array $prices)
    {
        $formattedPrices = [];

        foreach ($prices as $key => $value) {
            $formattedPrices[$key]['value'] = $value->getValue();
            $formattedPrices[$key]['currency'] = $value->getCurrency();
        }

        return $formattedPrices;
    }
}
