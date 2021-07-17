<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\RelatedItem\UpsellProduct;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\RelatedItem\UpsellProduct;
use Oro\Bundle\ProductBundle\Entity\Repository\RelatedItem\UpsellProductRepository;
use Oro\Bundle\ProductBundle\RelatedItem\AbstractRelatedItemConfigProvider;
use Oro\Bundle\ProductBundle\RelatedItem\UpsellProduct\AssignerDatabaseStrategy;
use Oro\Bundle\ProductBundle\RelatedItem\UpsellProduct\UpsellProductConfigProvider;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Component\Testing\Unit\EntityTrait;

class AssignerDatabaseStrategyTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var AssignerDatabaseStrategy */
    protected $assigner;

    /** @var UpsellProductConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $configProvider;

    /** @var UnitOfWork|\PHPUnit\Framework\MockObject\MockObject */
    protected $unitOfWork;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $entityManager;

    /** @var UpsellProductRepository|\PHPUnit\Framework\MockObject\MockObject */
    protected $upsellProductRepository;

    protected function setUp(): void
    {
        $this->configProvider = $this->getMockBuilder(AbstractRelatedItemConfigProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->upsellProductRepository = $this->getMockBuilder(UpsellProductRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->unitOfWork = $this->getMockBuilder(UnitOfWork::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityManager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityManager->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($this->unitOfWork);
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper
            ->expects($this->any())
            ->method('getEntityRepository')
            ->with(UpsellProduct::class)
            ->willReturn($this->upsellProductRepository);
        $this->doctrineHelper
            ->expects($this->any())
            ->method('getEntityManager')
            ->with(UpsellProduct::class)
            ->willReturn($this->entityManager);
        $this->assigner = new AssignerDatabaseStrategy($this->doctrineHelper, $this->configProvider);
    }

    public function testProductsCanBeAssignedToTheOther()
    {
        $productFrom = new Product();
        $productTo = new Product();
        $this->relationDoesNotExistInDatabase();
        $this->getLimitShouldReturn(1);
        $this->upsellProductsAreEnabled();
        $this->newRelationShouldBePersisted($this->createUpsellProduct($productFrom, $productTo));
        $this->assigner->addRelations($productFrom, [$productTo]);
    }

    public function testManyProductsCanBeAssignedToProduct()
    {
        $productFrom = new Product();
        $productsTo = [new Product(), new Product()];
        $this->relationDoesNotExistInDatabase();
        $this->getLimitShouldReturn(2);
        $this->upsellProductsAreEnabled();
        $this->newRelationsShouldBePersisted(2);
        $this->assigner->addRelations($productFrom, $productsTo);
    }

    public function testProductCannotBeAssignedToItself()
    {
        $productFrom = new Product();
        $this->upsellProductsAreEnabled();
        $this->getLimitShouldReturn(1);
        $this->relationDoesNotExistInDatabase();
        $this->newRelationShouldNotBePersisted();
        $this->expectException(\InvalidArgumentException::class);
        $this->assigner->addRelations($productFrom, [$productFrom]);
    }

    public function testProductWillNotBeAssignedIfRelationAlreadyExistsInDatabase()
    {
        $productFrom = new Product();
        $productTo = new Product();
        $this->getLimitShouldReturn(2);
        $this->relationExistsInDatabase();
        $this->upsellProductsAreEnabled();
        $this->newRelationShouldNotBePersisted();
        $this->assigner->addRelations($productFrom, [$productTo]);
    }

    public function testProductCanBeUnassignedFromDatabaseRelation()
    {
        $productFrom = new Product();
        $productTo = new Product();
        $upsellProduct = $this->createUpsellProduct($productFrom, $productTo);
        $this->getLimitShouldReturn(2);
        $this->upsellProductsAreEnabled();
        $this->findOneByShouldReturnRelation($productFrom, $productTo, $upsellProduct);
        $this->scheduledRelationShouldBeRemoved($upsellProduct);
        $this->assigner->removeRelations($productFrom, [$productTo]);
    }

    public function testNothingHappensWhenTryToRemoveNonExistingRelation()
    {
        $productFrom = new Product();
        $productTo = new Product();
        $this->upsellProductsAreEnabled();
        $this->findOneByShouldReturnNull($productFrom, $productTo);
        $this->assigner->removeRelations($productFrom, [$productTo]);
    }

    public function testNothingHappensWhenTryToRemoveNoElements()
    {
        $this->noRelationShouldBeRemoved();
        $this->doctrineHelper->expects($this->never())
            ->method($this->anything());

        $this->assigner->removeRelations(new Product(), []);
    }

    public function testThrowExceptionWhenTryToExceedRelationLimitForAProduct()
    {
        $productFrom = new Product();
        $productsTo = [new Product(), new Product()];
        $this->getLimitShouldReturn(1);
        $this->upsellProductsAreEnabled();
        $this->repositoryShouldReturnCountRelationsForProduct(0);
        $this->expectException(\OverflowException::class);
        $this->assigner->addRelations($productFrom, $productsTo);
    }

    public function testThrowExceptionWhenTryToExceedRelationLimitWhenSomeRelationsExistInDatabase()
    {
        $productFrom = new Product();
        $productTo = new Product();
        $this->getLimitShouldReturn(1);
        $this->upsellProductsAreEnabled();
        $this->repositoryShouldReturnCountRelationsForProduct(1);
        $this->expectException(\OverflowException::class);
        $this->assigner->addRelations($productFrom, [$productTo]);
    }

    public function testRelationCannotBeAssignedIfUpsellProductIsDisable()
    {
        $productFrom = new Product();
        $productTo = new Product();
        $this->getLimitShouldReturn(1);
        $this->upsellProductsAreDisabled();
        $this->expectException(\LogicException::class);
        $this->assigner->addRelations($productFrom, [$productTo]);
    }

    /**
     * @param int $limit
     */
    private function getLimitShouldReturn($limit)
    {
        $this->configProvider->expects($this->any())
            ->method('getLimit')
            ->willReturn($limit);
    }

    private function upsellProductsAreEnabled()
    {
        $this->configProvider->expects($this->any())
            ->method('isEnabled')
            ->willReturn(true);
    }

    private function upsellProductsAreDisabled()
    {
        $this->configProvider->expects($this->any())
            ->method('isEnabled')
            ->willReturn(false);
    }

    /**
     * @param UpsellProduct $expectedUpsellProduct
     * @param int           $howManyTimes
     */
    private function newRelationShouldBePersisted(UpsellProduct $expectedUpsellProduct, $howManyTimes = 1)
    {
        $this->entityManager->expects($this->exactly($howManyTimes))
            ->method('persist')
            ->with($this->callback(function (UpsellProduct $upsellProduct) use ($expectedUpsellProduct) {
                return $upsellProduct->getProduct() === $expectedUpsellProduct->getProduct()
                    && $upsellProduct->getRelatedItem() === $expectedUpsellProduct->getRelatedItem();
            }));
    }

    /**
     * @param int $howManyTimes
     */
    private function newRelationsShouldBePersisted($howManyTimes)
    {
        $this->entityManager->expects($this->exactly($howManyTimes))->method('persist');
    }

    private function newRelationShouldNotBePersisted()
    {
        $this->entityManager->expects($this->never())->method('persist');
        $this->entityManager->expects($this->never())->method('flush');
    }

    private function noRelationShouldBeRemoved()
    {
        $this->entityManager->expects($this->never())->method('remove');
        $this->entityManager->expects($this->never())->method('flush');
    }

    /**
     * @param Product $productFrom
     * @param Product $productTo
     * @return UpsellProduct
     */
    private function createUpsellProduct(Product $productFrom, Product $productTo)
    {
        return (new UpsellProduct())->setProduct($productFrom)
            ->setRelatedItem($productTo);
    }

    private function relationDoesNotExistInDatabase()
    {
        $this->upsellProductRepository->expects($this->any())
            ->method('exists')
            ->willReturn(false);
    }

    private function relationExistsInDatabase()
    {
        $this->upsellProductRepository->expects($this->any())
            ->method('exists')
            ->willReturn(true);
    }

    private function scheduledRelationShouldBeRemoved($upsellProduct)
    {
        $this->entityManager->expects($this->once())
            ->method('remove')
            ->with($upsellProduct);
    }

    /**
     * @param Product       $productFrom
     * @param Product       $productTo
     * @param UpsellProduct $upsellProduct
     */
    private function findOneByShouldReturnRelation(
        Product $productFrom,
        Product $productTo,
        UpsellProduct $upsellProduct = null
    ) {
        $this->upsellProductRepository->expects($this->any())
            ->method('findOneBy')
            ->with(['product' => $productFrom, 'relatedItem' => $productTo], null)
            ->willReturn($upsellProduct);
    }

    private function findOneByShouldReturnNull(Product $productFrom, Product $productTo)
    {
        $this->upsellProductRepository->expects($this->any())
            ->method('findOneBy')
            ->with(['product' => $productFrom, 'relatedItem' => $productTo], null)
            ->willReturn(null);
    }

    /**
     * @param int $howMany
     */
    private function repositoryShouldReturnCountRelationsForProduct($howMany)
    {
        $this->upsellProductRepository->expects($this->any())
            ->method('countRelationsForProduct')
            ->willReturn($howMany);
    }
}
