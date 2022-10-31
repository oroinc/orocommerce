<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Model\Builder;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Model\Builder\QuickAddRowInputParser;
use Oro\Bundle\ProductBundle\Provider\ProductUnitsProvider;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

class QuickAddRowInputParserTest extends \PHPUnit\Framework\TestCase
{
    private ProductRepository|\PHPUnit\Framework\MockObject\MockObject $productRepository;

    private AclHelper|\PHPUnit\Framework\MockObject\MockObject $aclHelper;

    private QuickAddRowInputParser $quickAddRowInputParser;

    private NumberFormatter|\PHPUnit\Framework\MockObject\MockObject $numberFormatter;

    protected function setUp(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $this->productRepository = $this->createMock(ProductRepository::class);
        $productUnitsProvider = $this->createMock(ProductUnitsProvider::class);
        $this->aclHelper = $this->createMock(AclHelper::class);
        $this->numberFormatter = $this->createMock(NumberFormatter::class);

        $registry->method('getRepository')->willReturnMap([
            [Product::class, null, $this->productRepository],
        ]);

        $productUnitsProvider->method('getAvailableProductUnits')
            ->willReturn(
                [
                    'Element' => 'item',
                    'Stunde' => 'hour',
                ]
            );

        $this->quickAddRowInputParser = new QuickAddRowInputParser(
            $registry,
            $productUnitsProvider,
            $this->aclHelper,
            $this->numberFormatter
        );
    }

    /**
     * @param array $input
     * @param array $expected
     *
     * @dataProvider exampleRowFile
     */
    public function testCreateFromFileLine(array $input, array $expected): void
    {
        $this->numberFormatter->expects(self::once())
            ->method('parseFormattedDecimal')
            ->willReturnCallback(function ($value) {
                if (str_contains($value, ',')) {
                    return (float)str_replace(',', '.', $value);
                }

                if (str_contains($value, '.')) {
                    return false;
                }

                return $value;
            });

        $index = 0;
        $input = array_values($input);
        if (!array_key_exists(2, $input)) {
            $this->assertProductRepository();
        }

        $result = $this->quickAddRowInputParser->createFromFileLine($input, $index++);

        self::assertEquals($expected[0], $result->getSku());
        self::assertEquals($expected[1], $result->getQuantity());
        self::assertEquals($expected[2], $result->getUnit());

        self::assertEquals(1, $index);
    }

    /**
     * @return array
     */
    public function exampleRowFile(): array
    {
        return [
            [
                'input' => [
                    'productSku' => ' SKU5  ',
                    'productQuantity' => ' 4.5',
                    'productUnit' => 'item '
                ],
                'expected' => [
                    'SKU5',
                    4.5,
                    'item'
                ]
            ],
            [
                'input' => [
                    'productSku' => 'ss2',
                    'productQuantity' => '   6 ',
                    'productUnit' => 'liter'
                ],
                'expected' => [
                    'ss2',
                    6,
                    'liter'
                ]
            ],
            [
                'input' => [
                    'productSku' => 'ss2',
                    'productQuantity' => '   6 ',
                ],
                'expected' => [
                    'ss2',
                    '6',
                    'item'
                ]
            ],
            [
                'input' => [
                    'productSku' => ' SKU5  ',
                    'productQuantity' => ' 4,5',
                    'productUnit' => 'Stunde '
                ],
                'expected' => [
                    'SKU5',
                    4.5,
                    'hour'
                ]
            ],
            [
                'input' => [
                    'productSku' => ' SKU5  ',
                    'productQuantity' => ' 4.5',
                    'productUnit' => 'ELEMENT '
                ],
                'expected' => [
                    'SKU5',
                    '4.5',
                    'item'
                ]
            ],
        ];
    }

    /**
     * @dataProvider exampleRow
     */
    public function testCreateFromRequest($input, $expected): void
    {
        $this->numberFormatter->expects(self::never())
            ->method('parseFormattedDecimal');

        $index = 0;

        if (!array_key_exists('productUnit', $input)) {
            $this->assertProductRepository();
        }

        $result = $this->quickAddRowInputParser->createFromRequest($input, $index++);

        self::assertEquals($expected[0], $result->getSku());
        self::assertEquals($expected[1], $result->getQuantity());
        self::assertEquals($expected[2], $result->getUnit());

        self::assertEquals(1, $index);
    }

    /**
     * @dataProvider exampleRowFile
     */
    public function testCreateFromPasteTextLine($input, $expected): void
    {
        $this->numberFormatter->expects(self::once())
            ->method('parseFormattedDecimal')
            ->willReturnCallback(function ($value) {
                if (str_contains($value, ',')) {
                    return (float)str_replace(',', '.', $value);
                }

                if (str_contains($value, '.')) {
                    return false;
                }

                return $value;
            });

        $index = 0;
        $input = array_values($input);
        if (!array_key_exists(2, $input)) {
            $this->assertProductRepository();
        }

        $result = $this->quickAddRowInputParser->createFromCopyPasteTextLine($input, $index++);

        self::assertEquals($expected[0], $result->getSku());
        self::assertEquals($expected[1], $result->getQuantity());
        self::assertEquals($expected[2], $result->getUnit());

        self::assertEquals(1, $index);
    }

    public function exampleRow(): array
    {
        return [
            [
                'input' => [
                    'productSku' => ' SKU5  ',
                    'productQuantity' => ' 4.5',
                    'productUnit' => 'item '
                ],
                'expected' => [
                    'SKU5',
                    4.5,
                    'item'
                ]
            ],
            [
                'input' => [
                    'productSku' => 'ss2',
                    'productQuantity' => '   6 ',
                    'productUnit' => 'liter'
                ],
                'expected' => [
                    'ss2',
                    6,
                    'liter'
                ]
            ],
            [
                'input' => [
                    'productSku' => 'ss2',
                    'productQuantity' => '   6 ',
                ],
                'expected' => [
                    'ss2',
                    '6',
                    'item'
                ]
            ],
            [
                'input' => [
                    'productSku' => ' SKU5  ',
                    'productQuantity' => ' 4.5',
                    'productUnit' => 'Stunde '
                ],
                'expected' => [
                    'SKU5',
                    '4.5',
                    'hour'
                ]
            ],
            [
                'input' => [
                    'productSku' => ' SKU5  ',
                    'productQuantity' => ' 4.5',
                    'productUnit' => 'ELEMENT '
                ],
                'expected' => [
                    'SKU5',
                    '4.5',
                    'item'
                ]
            ],
        ];
    }

    private function assertProductRepository(): void
    {
        $query = $this->createMock(AbstractQuery::class);
        $query->expects(self::once())
            ->method('getOneOrNullResult')
            ->with(AbstractQuery::HYDRATE_SINGLE_SCALAR)
            ->willReturn('item');

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $this->productRepository
            ->expects(self::once())
            ->method('getPrimaryUnitPrecisionCodeQueryBuilder')
            ->willReturn($queryBuilder);

        $this->aclHelper
            ->expects(self::once())
            ->method('apply')
            ->with($queryBuilder)
            ->willReturn($query);
    }
}
