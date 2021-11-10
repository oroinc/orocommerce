<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\RelatedItem\RelatedProduct;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\RelatedItem\RelatedProduct;
use Oro\Bundle\ProductBundle\Entity\Repository\RelatedItem\RelatedProductRepository;
use Oro\Bundle\ProductBundle\RelatedItem\RelatedProduct\AssignerDatabaseStrategy;
use Oro\Bundle\ProductBundle\RelatedItem\RelatedProduct\RelatedProductsConfigProvider;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;

class AssignerDatabaseStrategyTest extends \PHPUnit\Framework\TestCase
{
    /** @var RelatedProductsConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $configProvider;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $entityManager;

    /** @var RelatedProductRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $relatedProductsRepository;

    /** @var AssignerDatabaseStrategy */
    private $assigner;

    protected function setUp(): void
    {
        $this->configProvider = $this->createMock(RelatedProductsConfigProvider::class);
        $this->relatedProductsRepository = $this->createMock(RelatedProductRepository::class);
        $this->entityManager = $this->createMock(EntityManager::class);

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->with(RelatedProduct::class)
            ->willReturn($this->relatedProductsRepository);
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityManager')
            ->with(RelatedProduct::class)
            ->willReturn($this->entityManager);

        $this->assigner = new AssignerDatabaseStrategy($this->doctrineHelper, $this->configProvider);
    }

    public function testProductsCanBeAssignedToTheOther()
    {
        $productFrom = new Product();
        $productTo = new Product();

        $this->relationDoesNotExistInDatabase();
        $this->getLimitShouldReturn(1);
        $this->relatedProductsAreEnabled();
        $this->newRelationShouldBePersisted($this->createRelatedProduct($productFrom, $productTo));

        $this->assigner->addRelations($productFrom, [$productTo]);
    }

    public function testManyProductsCanBeAssignedToProduct()
    {
        $productFrom = new Product();
        $productsTo = [new Product(), new Product()];

        $this->relationDoesNotExistInDatabase();
        $this->getLimitShouldReturn(2);
        $this->relatedProductsAreEnabled();
        $this->newRelationsShouldBePersisted(2);

        $this->assigner->addRelations($productFrom, $productsTo);
    }

    public function testProductCannotBeAssignedToItself()
    {
        $productFrom = new Product();
        $this->relatedProductsAreEnabled();
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
        $this->relatedProductsAreEnabled();
        $this->newRelationShouldNotBePersisted();

        $this->assigner->addRelations($productFrom, [$productTo]);
    }

    public function testProductCanBeUnassignedFromDatabaseRelation()
    {
        $productFrom = new Product();
        $productTo = new Product();
        $relatedProducts = $this->createRelatedProduct($productFrom, $productTo);
        $this->getLimitShouldReturn(2);
        $this->relatedProductsAreEnabled();
        $this->findOneByShouldReturnRelation($productFrom, $productTo, $relatedProducts);
        $this->scheduledRelationShouldBeRemoved($relatedProducts);

        $this->assigner->removeRelations($productFrom, [$productTo]);
    }

    public function testNothingHappensWhenTryToRemoveNonExistingRelation()
    {
        $productFrom = new Product();
        $productTo = new Product();

        $this->relatedProductsAreEnabled();
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
        $this->relatedProductsAreEnabled();
        $this->repositoryShouldReturnCountRelationsForProduct(0);

        $this->expectException(\OverflowException::class);

        $this->assigner->addRelations($productFrom, $productsTo);
    }

    public function testThrowExceptionWhenTryToExceedRelationLimitWhenSomeRelationsExistInDatabase()
    {
        $productFrom = new Product();
        $productTo = new Product();

        $this->getLimitShouldReturn(1);
        $this->relatedProductsAreEnabled();
        $this->repositoryShouldReturnCountRelationsForProduct(1);

        $this->expectException(\OverflowException::class);

        $this->assigner->addRelations($productFrom, [$productTo]);
    }

    public function testRelationCannotBeAssignedIfRelatedProductIsDisable()
    {
        $productFrom = new Product();
        $productTo = new Product();
        $this->getLimitShouldReturn(1);
        $this->relatedProductsAreDisabled();

        $this->expectException(\LogicException::class);

        $this->assigner->addRelations($productFrom, [$productTo]);
    }

    private function getLimitShouldReturn(int $limit)
    {
        $this->configProvider->expects($this->any())
            ->method('getLimit')
            ->willReturn($limit);
    }

    private function relatedProductsAreEnabled()
    {
        $this->configProvider->expects($this->any())
            ->method('isEnabled')
            ->willReturn(true);
    }

    private function relatedProductsAreDisabled()
    {
        $this->configProvider->expects($this->any())
            ->method('isEnabled')
            ->willReturn(false);
    }

    private function newRelationShouldBePersisted(RelatedProduct $expectedRelatedProduct, int $howManyTimes = 1)
    {
        $this->entityManager->expects($this->exactly($howManyTimes))
            ->method('persist')
            ->with($this->callback(function (RelatedProduct $relatedProducts) use ($expectedRelatedProduct) {
                return $relatedProducts->getProduct() === $expectedRelatedProduct->getProduct()
                    && $relatedProducts->getRelatedItem() === $expectedRelatedProduct->getRelatedItem();
            }));
    }

    private function newRelationsShouldBePersisted(int $howManyTimes)
    {
        $this->entityManager->expects($this->exactly($howManyTimes))
            ->method('persist');
    }

    private function newRelationShouldNotBePersisted()
    {
        $this->entityManager->expects($this->never())
            ->method('persist');
        $this->entityManager->expects($this->never())
            ->method('flush');
    }

    private function noRelationShouldBeRemoved()
    {
        $this->entityManager->expects($this->never())
            ->method('remove');
        $this->entityManager->expects($this->never())
            ->method('flush');
    }

    private function createRelatedProduct(Product $productFrom, Product $productTo): RelatedProduct
    {
        return (new RelatedProduct())->setProduct($productFrom)
            ->setRelatedItem($productTo);
    }

    private function relationDoesNotExistInDatabase()
    {
        $this->relatedProductsRepository->expects($this->any())
            ->method('exists')
            ->willReturn(false);
    }

    private function relationExistsInDatabase()
    {
        $this->relatedProductsRepository->expects($this->any())
            ->method('exists')
            ->willReturn(true);
    }

    private function scheduledRelationShouldBeRemoved($relatedProducts)
    {
        $this->entityManager->expects($this->once())
            ->method('remove')
            ->with($relatedProducts);
    }

    private function findOneByShouldReturnRelation(
        Product $productFrom,
        Product $productTo,
        RelatedProduct $relatedProducts = null
    ) {
        $this->relatedProductsRepository->expects($this->any())
            ->method('findOneBy')
            ->with(['product' => $productFrom, 'relatedItem' => $productTo], null)
            ->willReturn($relatedProducts);
    }

    private function findOneByShouldReturnNull(Product $productFrom, Product $productTo)
    {
        $this->relatedProductsRepository->expects($this->any())
            ->method('findOneBy')
            ->with(['product' => $productFrom, 'relatedItem' => $productTo], null)
            ->willReturn(null);
    }

    private function repositoryShouldReturnCountRelationsForProduct(int $howMany)
    {
        $this->relatedProductsRepository->expects($this->any())
            ->method('countRelationsForProduct')
            ->willReturn($howMany);
    }
}
