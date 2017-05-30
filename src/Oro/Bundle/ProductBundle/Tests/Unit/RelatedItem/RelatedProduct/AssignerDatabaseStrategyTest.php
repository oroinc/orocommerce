<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\RelatedProducts;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\UnitOfWork;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\RelatedItem\RelatedProduct;
use Oro\Bundle\ProductBundle\Entity\Repository\RelatedItem\RelatedProductRepository;
use Oro\Bundle\ProductBundle\RelatedItem\RelatedProduct\RelatedProductsConfigProvider;
use Oro\Bundle\ProductBundle\RelatedItem\RelatedProduct\AssignerDatabaseStrategy;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Component\Testing\Unit\EntityTrait;

class AssignerDatabaseStrategyTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var AssignerDatabaseStrategy */
    protected $assigner;

    /** @var RelatedProductsConfigProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $configProvider;

    /** @var UnitOfWork|\PHPUnit_Framework_MockObject_MockObject */
    protected $unitOfWork;

    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    private $doctrineHelper;

    /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $entityManager;

    /** @var RelatedProductRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $relatedProductsRepository;

    protected function setUp()
    {
        $this->configProvider = $this->getMockBuilder(RelatedProductsConfigProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->relatedProductsRepository = $this->getMockBuilder(RelatedProductRepository::class)
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
            ->with(RelatedProduct::class)
            ->willReturn($this->relatedProductsRepository);

        $this->doctrineHelper
            ->expects($this->any())
            ->method('getEntityManager')
            ->with(RelatedProduct::class)
            ->willReturn($this->entityManager);

        $this->assigner = new AssignerDatabaseStrategy($this->doctrineHelper, $this->configProvider);
    }

    public function testProductCanBeAssignedToTheOther()
    {
        $productA = new Product();
        $productB = new Product();

        $this->updateScheduledInsertions([]);
        $this->relationDoesNotExistInDatabase();
        $this->getLimitShouldReturn(1);
        $this->relatedProductsAreEnabled();
        $this->newRelationShouldBePersisted($this->createRelatedProducts($productA, $productB));

        $this->assigner->addRelation($productA, $productB);
    }

    public function testProductCannotBeAssignedToItself()
    {
        $productA = new Product();
        $this->relatedProductsAreEnabled();
        $this->relationDoesNotExistInDatabase();
        $this->newRelationShouldNotBePersisted();

        $this->expectException(\InvalidArgumentException::class);

        $this->assigner->addRelation($productA, $productA);
    }

    public function testProductWillNotBeAssignedIfRelationIsAlreadyScheduledForInsertion()
    {
        $productFrom = new Product();
        $productTo = new Product();

        $relatedProduct = $this->createRelatedProducts($productFrom, $productTo);

        $this->getLimitShouldReturn(2);
        $this->relatedProductsAreEnabled();
        $this->relationDoesNotExistInDatabase();
        $this->newRelationShouldNotBePersisted();
        $this->updateScheduledInsertions([$relatedProduct]);

        $this->assigner->addRelation($productFrom, $productTo);
    }

    public function testProductWillNotBeAssignedIfRelationAlreadyExistsInDatabase()
    {
        $productA = new Product();
        $productB = new Product();
        $this->updateScheduledInsertions([]);

        $this->getLimitShouldReturn(2);
        $this->relationExistsInDatabase();
        $this->relatedProductsAreEnabled();
        $this->newRelationShouldNotBePersisted();

        $this->assigner->addRelation($productA, $productB);
    }

    public function testProductCanBeUnassignedFromScheduledRelation()
    {
        $productA = new Product();
        $productB = new Product();
        $relatedProducts = $this->createRelatedProducts($productA, $productB);
        $this->getLimitShouldReturn(2);
        $this->relatedProductsAreEnabled();
        $this->updateScheduledInsertions([$relatedProducts]);
        $this->scheduledRelationShouldBeDetached($relatedProducts);

        $this->assigner->removeRelation($productA, $productB);
    }

    public function testProductCanBeUnassignedFromDatabaseRelation()
    {
        $productA = new Product();
        $productB = new Product();
        $relatedProducts = $this->createRelatedProducts($productA, $productB);
        $this->getLimitShouldReturn(2);
        $this->relatedProductsAreEnabled();
        $this->updateScheduledInsertions([]);
        $this->findOneByShouldReturnRelation($productA, $productB, $relatedProducts);
        $this->scheduledRelationShouldBeRemoved($relatedProducts);

        $this->assigner->removeRelation($productA, $productB);
    }

    public function testNothingHappensWhenTryToRemoveNonExistingRelation()
    {
        $productA = new Product();
        $productB = new Product();

        $this->relatedProductsAreEnabled();
        $this->updateScheduledInsertions([[]]);
        $this->findOneByShouldReturnNull($productA, $productB);

        $this->assigner->removeRelation($productA, $productB);
    }

    public function testThrowExceptionWhenTryToExceedRelationLimitForAProduct()
    {
        $productA = new Product();
        $productB = new Product();
        $productC = new Product();
        $this->getLimitShouldReturn(1);
        $this->relatedProductsAreEnabled();
        $this->repositoryShouldReturnCountRelationsForProduct(0);
        $this->updateScheduledInsertions([$this->createRelatedProducts($productA, $productC)]);

        $this->expectException(\OverflowException::class);

        $this->assigner->addRelation($productA, $productB);
    }

    public function testThrowExceptionWhenTryToExceedRelationLimitWhenSomeRelationsExistInDatabase()
    {
        $productFrom = new Product();
        $productTo = new Product();

        $this->getLimitShouldReturn(1);
        $this->relatedProductsAreEnabled();
        $this->repositoryShouldReturnCountRelationsForProduct(1);
        $this->updateScheduledInsertions([]);

        $this->expectException(\OverflowException::class);

        $this->assigner->addRelation($productFrom, $productTo);
    }

    public function testRelationCannotBeAssignedIfRelatedProductIsDisable()
    {
        $productA = new Product();
        $productB = new Product();
        $this->getLimitShouldReturn(1);
        $this->relatedProductsAreDisabled();

        $this->expectException(\LogicException::class);

        $this->assigner->addRelation($productA, $productB);
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

    /**
     * @param RelatedProduct $expectedRelatedProducts
     * @param int            $howManyTimes
     */
    private function newRelationShouldBePersisted(RelatedProduct $expectedRelatedProducts, $howManyTimes = 1)
    {
        $this->entityManager->expects($this->exactly($howManyTimes))
            ->method('persist')
            ->with($this->callback(function (RelatedProduct $relatedProducts) use ($expectedRelatedProducts) {
                return $relatedProducts->getProduct() === $expectedRelatedProducts->getProduct()
                    && $relatedProducts->getRelatedProduct() === $expectedRelatedProducts->getRelatedProduct();
            }));
    }

    private function newRelationShouldNotBePersisted()
    {
        $this->entityManager->expects($this->never())
            ->method('persist');
    }

    /**
     * @param Product $productA
     * @param Product $productB
     * @return RelatedProduct
     */
    private function createRelatedProducts(Product $productA, Product $productB)
    {
        return (new RelatedProduct())->setProduct($productA)
            ->setRelatedProduct($productB);
    }

    /**
     * @param array $scheduledInsertions
     */
    private function updateScheduledInsertions(array $scheduledInsertions)
    {
        $this->unitOfWork->expects($this->any())
            ->method('getScheduledEntityInsertions')
            ->willReturn($scheduledInsertions);
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

    /**
     * @param $relatedProducts
     */
    private function scheduledRelationShouldBeDetached($relatedProducts)
    {
        $this->entityManager->expects($this->once())
            ->method('detach')
            ->with($relatedProducts);
    }

    /**
     * @param $relatedProducts
     */
    private function scheduledRelationShouldBeRemoved($relatedProducts)
    {
        $this->entityManager->expects($this->once())
            ->method('remove')
            ->with($relatedProducts);
    }

    /**
     * @param Product        $productA
     * @param Product        $productB
     * @param RelatedProduct $relatedProducts
     */
    private function findOneByShouldReturnRelation(
        Product $productA,
        Product $productB,
        RelatedProduct $relatedProducts = null
    ) {
        $this->relatedProductsRepository->expects($this->any())
            ->method('findOneBy')
            ->with(['product' => $productA, 'relatedProduct', $productB], null)
            ->willReturn($relatedProducts);
    }

    /**
     * @param Product $productFrom
     * @param Product $productTo
     */
    private function findOneByShouldReturnNull(Product $productFrom, Product $productTo)
    {
        $this->relatedProductsRepository->expects($this->any())
            ->method('findOneBy')
            ->with(['product' => $productFrom, 'relatedProduct', $productTo], null)
            ->willReturn(null);
    }

    /**
     * @param int $howMany
     */
    private function repositoryShouldReturnCountRelationsForProduct($howMany)
    {
        $this->relatedProductsRepository->expects($this->any())
            ->method('countRelationsForProduct')
            ->willReturn($howMany);
    }
}
