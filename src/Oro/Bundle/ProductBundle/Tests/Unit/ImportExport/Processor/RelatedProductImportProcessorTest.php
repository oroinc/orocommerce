<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\ImportExport\Processor;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Converter\DataConverterInterface;
use Oro\Bundle\ImportExportBundle\Serializer\SerializerInterface;
use Oro\Bundle\ImportExportBundle\Strategy\Import\ImportStrategyHelper;
use Oro\Bundle\ImportExportBundle\Strategy\StrategyInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\RelatedItem\RelatedProduct;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Entity\Repository\RelatedItem\RelatedProductRepository;
use Oro\Bundle\ProductBundle\ImportExport\Processor\RelatedProductImportProcessor;
use Oro\Bundle\ProductBundle\RelatedItem\RelatedItemConfigProviderInterface;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Symfony\Contracts\Translation\TranslatorInterface;

class RelatedProductImportProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProductRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $productRepository;

    /** @var RelatedProductRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $relatedProductRepository;

    /** @var RelatedItemConfigProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $configProvider;

    /** @var ImportStrategyHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $importStrategyHelper;

    /** @var AclHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $aclHelper;

    /** @var ImportStrategyHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $serializer;

    /** @var ImportStrategyHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $context;

    /** @var DataConverterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $dataConverter;

    /** @var StrategyInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $strategy;

    /** @var RelatedProductImportProcessor */
    private $processor;

    /** @var array */
    private $item = ['SKU' => 'sku-1', 'Related SKUs' => 'sku-2,sku-3'];

    /** @var RelatedProduct */
    private $object;

    protected function setUp(): void
    {
        $this->productRepository = $this->createMock(ProductRepository::class);
        $this->relatedProductRepository = $this->createMock(RelatedProductRepository::class);

        $objectManager = $this->createMock(ObjectManager::class);
        $objectManager->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                [Product::class, $this->productRepository],
                [RelatedProduct::class, $this->relatedProductRepository],
            ]);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($objectManager);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->any())
            ->method('trans')
            ->willReturnArgument(0);

        $this->configProvider = $this->createMock(RelatedItemConfigProviderInterface::class);
        $this->importStrategyHelper = $this->createMock(ImportStrategyHelper::class);
        $this->aclHelper = $this->createMock(AclHelper::class);

        $this->context = $this->createMock(ContextInterface::class);
        $this->context->expects($this->any())
            ->method('getOption')
            ->with('entityName')
            ->willReturn(RelatedProduct::class);
        $this->context->expects($this->any())
            ->method('getConfiguration')
            ->willReturn([]);

        $this->serializer = $this->createMock(SerializerInterface::class);

        $this->dataConverter = $this->createMock(DataConverterInterface::class);
        $this->dataConverter->expects($this->any())
            ->method('convertToImportFormat')
            ->willReturnArgument(0);

        $this->strategy = $this->createMock(StrategyInterface::class);

        $this->processor = new RelatedProductImportProcessor(
            $registry,
            $translator,
            $this->configProvider,
            $this->importStrategyHelper,
            $this->aclHelper
        );

        $this->processor->setSerializer($this->serializer);
        $this->processor->setImportExportContext($this->context);
        $this->processor->setDataConverter($this->dataConverter);
        $this->processor->setStrategy($this->strategy);

        $this->object = new RelatedProduct();
        $this->object->setProduct(new Product());
        $this->object->setRelatedItem(new Product());
    }

    public function testProcessDisabled(): void
    {
        $this->configProvider->expects($this->once())
            ->method('isEnabled')
            ->willReturn(false);

        $this->strategy->expects($this->never())
            ->method('process');

        $this->assertNull($this->processor->process($this->item));
    }

    public function testProcessInvalidProduct(): void
    {
        $this->configProvider->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->context->expects($this->once())
            ->method('incrementErrorEntriesCount');

        $this->importStrategyHelper->expects($this->once())
            ->method('addValidationErrors')
            ->with(['oro.product.product_by_sku.not_found'], $this->context);

        $this->strategy->expects($this->never())
            ->method('process');

        $this->setUpQueryMock(null);

        $this->assertNull($this->processor->process($this->item));
    }

    /**
     * @dataProvider processWhenSkuColumnMissingDataProvider
     */
    public function testProcessWhenColumnMissing(array $item, string $expectedError): void
    {
        $this->configProvider->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->context->expects($this->once())
            ->method('incrementErrorEntriesCount');

        $this->importStrategyHelper->expects($this->once())
            ->method('addValidationErrors')
            ->with([$expectedError], $this->context);

        $this->strategy->expects($this->never())
            ->method('process');

        $this->assertNull($this->processor->process($item));
    }

    public function processWhenSkuColumnMissingDataProvider(): array
    {
        return [
            [
                'item' => ['sample_key' => 'sample_value', 'Related SKUs' => 'sample_value'],
                'expectedError' => 'oro.product.import.sku.column_missing',
            ],
            [
                'item' => ['sample_key' => 'sample_value', 'SKU' => 'sample_value'],
                'expectedError' => 'oro.product.import.related_sku.column_missing',
            ],
        ];
    }

    public function testProcessWhenEmptyRelatedSku(): void
    {
        $this->configProvider->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->context->expects($this->once())
            ->method('incrementErrorEntriesCount');

        $this->importStrategyHelper->expects($this->once())
            ->method('addValidationErrors')
            ->with(['oro.product.import.related_sku.empty_sku'], $this->context);

        $this->strategy->expects($this->never())
            ->method('process');

        $this->context->expects($this->once())
            ->method('incrementErrorEntriesCount');

        $this->setUpQueryMock(['id' => 42]);

        $this->assertNull(
            $this->processor->process(['SKU' => 'sku-1', 'Related SKUs' => 'sku-2,,sku-3'])
        );
    }

    public function testProcessWhenSelfRelation(): void
    {
        $this->configProvider->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->context->expects($this->once())
            ->method('incrementErrorEntriesCount');

        $this->importStrategyHelper->expects($this->once())
            ->method('addValidationErrors')
            ->with(['oro.product.import.related_sku.self_relation'], $this->context);

        $this->strategy->expects($this->never())
            ->method('process');

        $this->context->expects($this->once())
            ->method('incrementErrorEntriesCount');

        $this->setUpQueryMock(['id' => 42]);

        $this->assertNull($this->processor->process(['SKU' => 'sku-1', 'Related SKUs' => 'sku-1']));
    }

    public function testProcess(): void
    {
        $this->configProvider->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);
        $this->configProvider->expects($this->once())
            ->method('isBidirectional')
            ->willReturn(true);
        $this->configProvider->expects($this->once())
            ->method('getLimit')
            ->willReturn(3);

        $this->context->expects($this->atLeastOnce())
            ->method('setValue')
            ->withConsecutive(
                ['rawItemData', $this->anything()],
                ['itemData', ['SKU' => 'sku-1', 'Related SKUs' => 'sku-2']]
            );

        $this->serializer->expects($this->exactly(2))
            ->method('denormalize')
            ->withConsecutive(
                [['SKU' => 'sku-1', 'Related SKUs' => 'sku-2'], RelatedProduct::class, ''],
                [['SKU' => 'sku-1', 'Related SKUs' => 'sku-3'], RelatedProduct::class, '']
            )
            ->willReturn($this->object);

        $this->strategy->expects($this->exactly(2))
            ->method('process')
            ->with($this->object)
            ->willReturnArgument(0);

        $this->setUpQueryMock(['id' => 42]);

        $this->relatedProductRepository->expects($this->any())
            ->method('findRelatedIds')
            ->with(42, true)
            ->willReturn([1001]);

        $this->assertEquals([$this->object, $this->object], $this->processor->process($this->item));
    }

    public function testProcessInvalidLimit(): void
    {
        $this->configProvider->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);
        $this->configProvider->expects($this->once())
            ->method('isBidirectional')
            ->willReturn(true);
        $this->configProvider->expects($this->once())
            ->method('getLimit')
            ->willReturn(3);
        $this->context->expects($this->atLeastOnce())
            ->method('setValue')
            ->withConsecutive(
                ['rawItemData', $this->anything()],
                ['itemData', ['SKU' => 'sku-1', 'Related SKUs' => 'sku-2']]
            );
        $this->context->expects($this->once())
            ->method('incrementErrorEntriesCount');

        $this->serializer->expects($this->exactly(2))
            ->method('denormalize')
            ->withConsecutive(
                [['SKU' => 'sku-1', 'Related SKUs' => 'sku-2'], RelatedProduct::class, ''],
                [['SKU' => 'sku-1', 'Related SKUs' => 'sku-3'], RelatedProduct::class, '']
            )
            ->willReturn($this->object);

        $this->strategy->expects($this->exactly(2))
            ->method('process')
            ->with($this->object)
            ->willReturnArgument(0);

        $this->setUpQueryMock(['id' => 42]);

        $this->relatedProductRepository->expects($this->any())
            ->method('findRelatedIds')
            ->with(42, true)
            ->willReturn([1001, 2002]);

        $this->importStrategyHelper->expects($this->once())
            ->method('addValidationErrors')
            ->with(['oro.product.import.related_sku.max_relations'], $this->context);

        $this->assertEquals([], $this->processor->process($this->item));
    }

    private function setUpQueryMock(?array $result): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn($result);

        $this->aclHelper->expects($this->once())
            ->method('apply')
            ->with($queryBuilder)
            ->willReturn($query);

        $this->productRepository->expects($this->once())
            ->method('getProductIdBySkuQueryBuilder')
            ->with('sku-1')
            ->willReturn($queryBuilder);
    }
}
