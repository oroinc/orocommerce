<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Datagrid\Provider;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PricingBundle\Datagrid\Provider\ProductPriceProvider;
use Oro\Bundle\PricingBundle\Formatter\ProductPriceFormatter;
use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceDTO;
use Oro\Bundle\PricingBundle\Model\ProductPriceInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteria;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Component\Testing\Unit\EntityTrait;

class ProductPriceProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private const USER_CURRENCY = 'USD';

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var ProductPriceFormatter|\PHPUnit\Framework\MockObject\MockObject */
    private $priceFormatter;

    /** @var ProductPriceProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $productPriceProvider;

    /** @var ProductPriceProvider */
    private $gridProductPriceProvider;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->priceFormatter = $this->createMock(ProductPriceFormatter::class);
        $this->productPriceProvider = $this->createMock(ProductPriceProviderInterface::class);

        $this->gridProductPriceProvider = new ProductPriceProvider(
            $this->productPriceProvider,
            $this->priceFormatter,
            $this->doctrineHelper
        );
    }

    public function testGetPricesForProductsByPriceListWithoutPrices()
    {
        $productScopeCriteria = new ProductPriceScopeCriteria();
        $this->productPriceProvider->expects($this->once())
            ->method('getPricesByScopeCriteriaAndProducts')
            ->with($productScopeCriteria, [], [self::USER_CURRENCY], null)
            ->willReturn([]);

        $this->priceFormatter->expects($this->never())
            ->method('formatProductPrice');

        $this->doctrineHelper->expects($this->never())
            ->method('getEntityReference');

        $result = $this->gridProductPriceProvider->getPricesForProductsByPriceList(
            [],
            $productScopeCriteria,
            self::USER_CURRENCY
        );

        $this->assertEquals([], $result);
    }

    /**
     * @dataProvider pricesDataProvider
     */
    public function testGetPricesForProductsByPriceList(array $productPrices, array $expected)
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
                [self::USER_CURRENCY],
                null
            )
            ->willReturn($productPrices);

        $this->priceFormatter->expects($this->exactly(2))
            ->method('formatProductPrice')
            ->willReturnCallback(function (ProductPriceInterface $price) {
                $priceValue = $price->getPrice()->getValue();
                $priceCurrency = $price->getPrice()->getCurrency();
                $unitCode = $price->getUnit()->getCode();

                return [
                    'price' => $priceValue,
                    'currency' => $priceCurrency,
                    'quantity' => $price->getQuantity(),
                    'unit' => $unitCode,
                    'formatted_price' => $priceValue . ' ' . $priceCurrency,
                    'formatted_unit' => $unitCode . ' FORMATTED',
                    'quantity_with_unit' => $price->getQuantity() . ' ' . $unitCode
                ];
            });

        $combinedPricesForProductsByPriceList = $this->gridProductPriceProvider->getPricesForProductsByPriceList(
            [new ResultRecord(['id' => 1])],
            $productScopeCriteria,
            self::USER_CURRENCY
        );

        $this->assertEquals(
            $expected,
            $combinedPricesForProductsByPriceList
        );
    }

    public function pricesDataProvider(): array
    {
        return [
            [
                'productPrices' => [
                    1 => [
                        new ProductPriceDTO(
                            $this->getEntity(Product::class, ['id' => 1]),
                            Price::create(10, 'USD'),
                            5,
                            $this->getEntity(ProductUnit::class, ['code' => 'item'])
                        ),
                        new ProductPriceDTO(
                            $this->getEntity(Product::class, ['id' => 1]),
                            Price::create(20, 'USD'),
                            5,
                            $this->getEntity(ProductUnit::class, ['code' => 'item'])
                        ),
                        new ProductPriceDTO(
                            $this->getEntity(Product::class, ['id' => 1]),
                            Price::create(10, 'USD'),
                            1,
                            $this->getEntity(ProductUnit::class, ['code' => 'unit'])
                        )
                    ]
                ],
                'expected' => [
                    1 => [
                        'item_5' => [
                            'price' => 10,
                            'currency' => 'USD',
                            'quantity' => 5,
                            'unit' => 'item',
                            'formatted_price' => '10 USD',
                            'formatted_unit' => 'item FORMATTED',
                            'quantity_with_unit' => '5 item'
                        ],
                        'unit_1' => [
                            'price' => 10,
                            'currency' => 'USD',
                            'quantity' => 1,
                            'unit' => 'unit',
                            'formatted_price' => '10 USD',
                            'formatted_unit' => 'unit FORMATTED',
                            'quantity_with_unit' => '1 unit'
                        ]
                    ]
                ]
            ]
        ];
    }
}
