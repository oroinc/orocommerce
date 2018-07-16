<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Provider;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PricingBundle\Entity\BasePriceList;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Provider\MatchingPriceProvider;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Component\Testing\Unit\EntityTrait;

class MatchingPriceProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    const PRODUCT_CLASS = 'ProductClass';
    const PRODUCT_UNIT_CLASS = 'ProductUnitClass';

    /** @var ProductPriceProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $productPriceProvider;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $doctrineHelper;

    /** @var MatchingPriceProvider */
    protected $provider;

    protected function setUp()
    {
        $this->productPriceProvider = $this->createMock(ProductPriceProviderInterface::class);

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
        // TODO: BB-14587 fix me
        $this->markTestIncomplete('BB-14587');

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
        $product = $this->getEntity('\Oro\Bundle\ProductBundle\Entity\Product', ['id' => $productId]);
        /** @var BasePriceList $priceList */
        $priceList = $this->getEntity('\Oro\Bundle\PricingBundle\Entity\BasePriceList', ['id' => 2]);

        /** @var ProductUnit $productUnit */
        $productUnit = $this->getEntity(
            '\Oro\Bundle\ProductBundle\Entity\ProductUnit',
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
            ->with([new ProductPriceCriteria($product, $productUnit, $qty, $currency)], $priceList->getId())
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
