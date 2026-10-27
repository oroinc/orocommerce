<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Datagrid\Datagrid;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductVariantLink;
use Oro\Bundle\ProductBundle\Event\DatagridLineItemsDataEvent;
use Oro\Bundle\ProductBundle\EventListener\DatagridLineItemsDataRestrictVariantViewListener;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductLineItemStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DatagridLineItemsDataRestrictVariantViewListenerTest extends TestCase
{
    private const FEATURE = 'simple_variations_view_restriction';

    private ManagerRegistry|MockObject $doctrine;

    private FeatureChecker|MockObject $featureChecker;

    private DatagridLineItemsDataRestrictVariantViewListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->listener = new DatagridLineItemsDataRestrictVariantViewListener($this->doctrine);
        $this->listener->setFeatureChecker($this->featureChecker);
        $this->listener->addFeature(self::FEATURE);
    }

    private function createEvent(array $lineItems, array $lineItemsData = []): DatagridLineItemsDataEvent
    {
        return new DatagridLineItemsDataEvent($lineItems, $lineItemsData, $this->createMock(Datagrid::class), []);
    }

    private function expectVariantIdsQuery(array $productIds, array $variantIds): void
    {
        $query = $this->createMock(AbstractQuery::class);
        $query->expects(self::once())
            ->method('getArrayResult')
            ->willReturn(array_map(static fn (int $id) => ['productId' => (string)$id], $variantIds));

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects(self::once())
            ->method('select')
            ->with('DISTINCT IDENTITY(pvl.product) AS productId')
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('where')
            ->with('pvl.product IN (:productIds)')
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('setParameter')
            ->with('productIds', $productIds, ArrayParameterType::INTEGER)
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::once())
            ->method('createQueryBuilder')
            ->with('pvl')
            ->willReturn($queryBuilder);

        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(ProductVariantLink::class)
            ->willReturn($repository);
    }

    public function testOnLineItemDataWhenFeatureDisabled(): void
    {
        $lineItem = (new ProductLineItemStub(10))->setProduct((new ProductStub())->setId(101));
        $event = $this->createEvent([10 => $lineItem], [10 => ['isVisible' => true]]);

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with(self::FEATURE, null)
            ->willReturn(false);
        $this->doctrine->expects(self::never())
            ->method('getRepository');

        $this->listener->onLineItemData($event);

        self::assertEquals(['isVisible' => true], $event->getDataForLineItem(10));
    }

    public function testOnLineItemDataWhenNoApplicableLineItems(): void
    {
        $parentProduct = (new ProductStub())->setId(301)->setType(Product::TYPE_CONFIGURABLE);
        $lineItemWithParent = (new ProductLineItemStub(10))
            ->setProduct((new ProductStub())->setId(101))
            ->setParentProduct($parentProduct);
        $lineItemWithoutProduct = new ProductLineItemStub(20);
        $configurableLineItem = (new ProductLineItemStub(30))->setProduct($parentProduct);
        $event = $this->createEvent(
            [10 => $lineItemWithParent, 20 => $lineItemWithoutProduct, 30 => $configurableLineItem],
            [10 => ['isVisible' => true], 20 => ['isVisible' => false], 30 => ['isVisible' => true]]
        );

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with(self::FEATURE, null)
            ->willReturn(true);
        $this->doctrine->expects(self::never())
            ->method('getRepository');

        $this->listener->onLineItemData($event);

        self::assertEquals(['isVisible' => true], $event->getDataForLineItem(10));
        self::assertEquals(['isVisible' => false], $event->getDataForLineItem(20));
        self::assertEquals(['isVisible' => true], $event->getDataForLineItem(30));
    }

    public function testOnLineItemDataMarksVariantsWithoutParentProductAsNotVisible(): void
    {
        $variant = (new ProductStub())->setId(101);
        $simple = (new ProductStub())->setId(201);
        $variantLineItem = (new ProductLineItemStub(10))->setProduct($variant);
        $sameVariantLineItem = (new ProductLineItemStub(11))->setProduct($variant);
        $simpleLineItem = (new ProductLineItemStub(20))->setProduct($simple);
        $event = $this->createEvent(
            [10 => $variantLineItem, 11 => $sameVariantLineItem, 20 => $simpleLineItem],
            [10 => ['isVisible' => true, 'sku' => 'SKU1'], 11 => ['isVisible' => true], 20 => ['isVisible' => true]]
        );

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with(self::FEATURE, null)
            ->willReturn(true);
        $this->expectVariantIdsQuery([101, 201], [101]);

        $this->listener->onLineItemData($event);

        self::assertEquals(['isVisible' => false, 'sku' => 'SKU1'], $event->getDataForLineItem(10));
        self::assertEquals(['isVisible' => false], $event->getDataForLineItem(11));
        self::assertEquals(['isVisible' => true], $event->getDataForLineItem(20));
    }
}
