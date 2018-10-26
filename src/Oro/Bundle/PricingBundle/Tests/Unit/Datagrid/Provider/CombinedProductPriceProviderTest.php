<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Datagrid\Provider;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PricingBundle\Datagrid\Provider\CombinedProductPriceProvider;
use Oro\Bundle\PricingBundle\Formatter\ProductPriceFormatter;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteria;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\Testing\Unit\EntityTrait;

class CombinedProductPriceProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var ProductPriceFormatter|\PHPUnit\Framework\MockObject\MockObject */
    private $priceFormatter;

    /** @var ProductPriceProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $productPriceProvider;

    /** @var CombinedProductPriceProvider */
    private $combinedProductPriceProvider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->priceFormatter = $this->createMock(ProductPriceFormatter::class);
        $this->productPriceProvider = $this->createMock(ProductPriceProviderInterface::class);

        $this->combinedProductPriceProvider = new CombinedProductPriceProvider(
            $this->productPriceProvider,
            $this->priceFormatter,
            $this->doctrineHelper
        );
    }

    protected function tearDown()
    {
        unset(
            $this->doctrineHelper,
            $this->productPriceProvider,
            $this->priceFormatter,
            $this->combinedProductPriceProvider
        );
    }

    public function testGetCombinedPricesForProductsByPriceListWithoutPrices()
    {
        $productScopeCriteria = new ProductPriceScopeCriteria();
        $this->productPriceProvider->expects($this->once())
            ->method('getPricesByScopeCriteriaAndProducts')
            ->with($productScopeCriteria, [], null, null)
            ->willReturn([]);

        $this->priceFormatter->expects($this->never())
            ->method('formatProductPriceData');

        $this->doctrineHelper->expects($this->never())
            ->method('getEntityReference');

        $result = $this->combinedProductPriceProvider
            ->getCombinedPricesForProductsByPriceList(
                [],
                $productScopeCriteria
            );

        $this->assertEquals([], $result);
    }

    /**
     * @dataProvider pricesDataProvider
     *
     * @param array $productPrices
     * @param array $expected
     */
    public function testGetCombinedPricesForProductsByPriceList(array $productPrices, array $expected)
    {
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->willReturnCallback(function ($className, $id) {
                return $this->getEntity($className, ['id' => $id]);
            });

        $productScopeCriteria = new ProductPriceScopeCriteria();
        $this->productPriceProvider->expects($this->once())
            ->method('getPricesByScopeCriteriaAndProducts')
            ->with(
                $productScopeCriteria,
                [$this->getEntity(Product::class, ['id' => 1])],
                null,
                null
            )
            ->willReturn($productPrices);

        $this->priceFormatter->expects($this->exactly(2))
            ->method('formatProductPriceData')
            ->willReturnCallback(function (array $price) {
                return $price;
            });

        $combinedPricesForProductsByPriceList = $this->combinedProductPriceProvider
            ->getCombinedPricesForProductsByPriceList(
                [new ResultRecord(['id' => 1])],
                $productScopeCriteria
            );

        $this->assertEquals(
            $expected,
            $combinedPricesForProductsByPriceList
        );
    }

    /**
     * @return array
     */
    public function pricesDataProvider(): array
    {
        return [
            [
                'productPrices' => [
                    1 => [
                        [
                            'price' => 10,
                            'currency' => 'USD',
                            'quantity' => 5,
                            'unit' => 'item'
                        ],
                        [
                            'price' => 20,
                            'currency' => 'USD',
                            'quantity' => 5,
                            'unit' => 'item'
                        ],
                        [
                            'price' => 10,
                            'currency' => 'USD',
                            'quantity' => 1,
                            'unit' => 'unit'
                        ]
                    ]
                ],
                'expected' => [
                    1 => [
                        'item_5' => [
                            'price' => 10,
                            'currency' => 'USD',
                            'quantity' => 5,
                            'unit' => 'item'
                        ],
                        'unit_1' => [
                            'price' => 10,
                            'currency' => 'USD',
                            'quantity' => 1,
                            'unit' => 'unit'
                        ]
                    ]
                ]
            ]
        ];
    }
}
