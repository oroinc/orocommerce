<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Model\Builder;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Model\Builder\QuickAddRowInputParser;
use Oro\Bundle\ProductBundle\Provider\ProductUnitsProvider;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

class QuickAddRowInputParserTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var ProductUnitsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $productUnitsProvider;

    /** @var ProductRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $productRepository;

    /** @var AclHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $aclHelper;

    /** @var QuickAddRowInputParser */
    private $quickAddRowInputParser;

    public function setUp()
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->productRepository = $this->createMock(ProductRepository::class);
        $this->productUnitsProvider = $this->createMock(ProductUnitsProvider::class);
        $this->aclHelper = $this->createMock(AclHelper::class);

        $this->registry->method('getRepository')->willReturnMap([
            [Product::class, null, $this->productRepository],
        ]);

        $this->productUnitsProvider->method('getAvailableProductUnits')
            ->willReturn(
                [
                    'Element' => 'item',
                    'Stunde' => 'hour',
                ]
            );

        $this->quickAddRowInputParser = new QuickAddRowInputParser(
            $this->registry,
            $this->productUnitsProvider,
            $this->aclHelper
        );
    }

    /**
     * @param array $input
     * @param array $expected
     *
     * @dataProvider exampleRow
     */
    public function testCreateFromFileLine($input, $expected)
    {
        $index = 0;
        $input = array_values($input);
        if (!array_key_exists(2, $input)) {
            $this->assertProductRepository();
        }

        $result = $this->quickAddRowInputParser->createFromFileLine($input, $index++);

        $this->assertEquals($expected[0], $result->getSku());
        $this->assertEquals($expected[1], $result->getQuantity());
        $this->assertEquals($expected[2], $result->getUnit());

        $this->assertEquals(1, $index);
    }

    /**
     * @param $input
     * @param $expected
     *
     * @dataProvider exampleRow
     */
    public function testCreateFromRequest($input, $expected)
    {
        $index = 0;

        if (!array_key_exists('productUnit', $input)) {
            $this->assertProductRepository();
        }

        $result = $this->quickAddRowInputParser->createFromRequest($input, $index++);

        $this->assertEquals($expected[0], $result->getSku());
        $this->assertEquals($expected[1], $result->getQuantity());
        $this->assertEquals($expected[2], $result->getUnit());

        $this->assertEquals(1, $index);
    }

    /**
     * @param $input
     * @param $expected
     *
     * @dataProvider exampleRow
     */
    public function testCreateFromPasteTextLine($input, $expected)
    {
        $index = 0;
        $input = array_values($input);

        if (!array_key_exists(2, $input)) {
            $this->assertProductRepository();
        }

        $result = $this->quickAddRowInputParser->createFromCopyPasteTextLine($input, $index++);

        $this->assertEquals($expected[0], $result->getSku());
        $this->assertEquals($expected[1], $result->getQuantity());
        $this->assertEquals($expected[2], $result->getUnit());

        $this->assertEquals(1, $index);
    }

    /**
     * @return array
     */
    public function exampleRow()
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
                    '4.5',
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
                    '6',
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

    private function assertProductRepository()
    {
        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getOneOrNullResult')
            ->with(AbstractQuery::HYDRATE_SINGLE_SCALAR)
            ->willReturn('item');

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $this->productRepository
            ->expects($this->once())
            ->method('getPrimaryUnitPrecisionCodeQueryBuilder')
            ->willReturn($queryBuilder);

        $this->aclHelper
            ->expects($this->once())
            ->method('apply')
            ->with($queryBuilder)
            ->willReturn($query);
    }
}
