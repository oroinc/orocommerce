<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Duplicator;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Duplicator\SkuIncrementor;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

class SkuIncrementorTest extends \PHPUnit\Framework\TestCase
{
    private const PRODUCT_CLASS = 'OroProductBundle:Product';

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var AclHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $aclHelper;

    /** @var SkuIncrementor */
    private $service;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->aclHelper = $this->createMock(AclHelper::class);

        $this->service = new SkuIncrementor($this->doctrineHelper, $this->aclHelper, self::PRODUCT_CLASS);
    }

    /**
     * @dataProvider skuDataProvider
     */
    public function testIncrementSku(array $existingSku, array $testCases)
    {
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->with(self::PRODUCT_CLASS)
            ->willReturn($this->getProductRepository($existingSku));

        foreach ($testCases as $expected => $sku) {
            $this->assertEquals($expected, $this->service->increment($sku));
        }
    }

    public function skuDataProvider(): array
    {
        return [
            [
                [
                    ['sku' => 'ABC123'],
                    ['sku' => 'ABC123-66'],
                    ['sku' => 'ABC123-77'],
                    ['sku' => 'ABC123-88'],
                    ['sku' => 'ABC123-88abc'],
                ],
                [
                    'ABC123-89' => 'ABC123-77',
                    'ABC123-90' => 'ABC123-77',
                    'ABC123-91' => 'ABC123-66'
                ]
            ],
            [
                [
                    ['sku' => 'DEF123-66'],
                    ['sku' => 'DEF123-88']
                ],
                [
                    'DEF123-66-1' => 'DEF123-66',
                    'DEF123-66-2' => 'DEF123-66',
                    'DEF123-88-1' => 'DEF123-88',
                    'DEF123-88-2' => 'DEF123-88',
                ]
            ],
            [
                [
                    ['sku' => 'SKU-001-updated'],
                    ['sku' => 'SKU-001-updated-1']
                ],
                [
                    'SKU-001-updated-2' => 'SKU-001-updated-1',
                ]
            ],
            [
                [
                    ['sku' => 'SKU-001-updated-1']
                ],
                [
                    'SKU-001-updated-1-1' => 'SKU-001-updated-1',
                ]
            ],
        ];
    }

    private function getProductRepository(array $existingSku): ProductRepository
    {
        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->any())
            ->method('getResult')
            ->willReturn($existingSku);

        $queryBuilder = $this->createMock(QueryBuilder::class);

        $repository = $this->createMock(ProductRepository::class);
        $repository->expects($this->any())
            ->method('getAllSkuByPatternQueryBuilder')
            ->withAnyParameters()
            ->willReturn($queryBuilder);
        $repository->expects($this->any())
            ->method('getBySkuQueryBuilder')
            ->willReturnCallback(function ($sku) use ($queryBuilder, $query, $existingSku) {
                $query->expects($this->any())
                    ->method('getOneOrNullResult')
                    ->willReturnCallback(function () use ($existingSku, $sku) {
                        return in_array($sku, array_column($existingSku, 'sku'), true) ? new Product() : null;
                    });

                return $queryBuilder;
            });

        $this->aclHelper->expects($this->any())
            ->method('apply')
            ->with($queryBuilder)
            ->willReturn($query);

        return $repository;
    }
}
