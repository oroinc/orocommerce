<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivity;
use Oro\Bundle\VisibilityBundle\Entity\EntityListener\CategoryDefaultVisibilityListener;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository\CategoryRepository;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;

class CategoryDefaultVisibilityListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private CategoryDefaultVisibilityListener $categoryDefaultVisibilityListener;
    private ScopeManager|MockObject $scopeManager;
    private InsertFromSelectQueryExecutor|MockObject $executor;

    #[\Override]
    protected function setUp(): void
    {
        $this->scopeManager = $this->createMock(ScopeManager::class);
        $this->executor = $this->createMock(InsertFromSelectQueryExecutor::class);

        $this->categoryDefaultVisibilityListener = new CategoryDefaultVisibilityListener(
            $this->scopeManager,
            $this->executor
        );
    }

    public function testPostFlush(): void
    {
        $parentCategory = $this->getEntity(Category::class, ['id' => 1]);
        $childCategory = $this->getEntity(Category::class, ['id' => 2, 'parentCategory' => $parentCategory]);
        $activity = $this->getEntity(TestActivity::class, ['id' => 3]);
        $childWithoutParentCategory = $this->getEntity(Category::class, ['id' => 4]);

        $this->assertOnFlush([$parentCategory, $childCategory, $activity, $childWithoutParentCategory]);
        $event = $this->assertOnPostFlush($childCategory);
        $this->categoryDefaultVisibilityListener->postFlush($event);
    }

    private function assertOnPostFlush($childCategory): PostFlushEventArgs
    {
        $scope = new Scope();
        $categoryRepository = $this->createMock(CategoryRepository::class);
        $categoryRepository
            ->expects($this->once())
            ->method('find')
            ->willReturn([]);
        $categoryRepository
            ->expects($this->once())
            ->method('insertParentCategoryValues')
            ->with($this->executor, [$childCategory->getId()], CategoryVisibilityResolved::VISIBILITY_VISIBLE, $scope);

        $this->scopeManager
            ->expects($this->once())
            ->method('findDefaultScope')
            ->willReturn($scope);

        $objectManager = $this->createMock(EntityManagerInterface::class);
        $objectManager
            ->expects($this->any())
            ->method('getRepository')
            ->with(CategoryVisibilityResolved::class)
            ->willReturn($categoryRepository);

        return new PostFlushEventArgs($objectManager);
    }

    private function assertOnFlush(array $entities): void
    {
        $unitOfWork = $this->createMock(UnitOfWork::class);
        $unitOfWork
            ->expects($this->any())
            ->method('getScheduledEntityInsertions')
            ->willReturn($entities);

        $categoryRepository = $this->createMock(CategoryRepository::class);
        $categoryRepository
            ->expects($this->any())
            ->method('getFallbackToAllVisibility')
            ->willReturn(CategoryVisibilityResolved::VISIBILITY_VISIBLE);

        $objectManager = $this->createMock(EntityManagerInterface::class);
        $objectManager
            ->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);
        $objectManager
            ->expects($this->any())
            ->method('getRepository')
            ->with(CategoryVisibilityResolved::class)
            ->willReturn($categoryRepository);

        $this->categoryDefaultVisibilityListener->onFlush(new OnFlushEventArgs($objectManager));
    }
}
