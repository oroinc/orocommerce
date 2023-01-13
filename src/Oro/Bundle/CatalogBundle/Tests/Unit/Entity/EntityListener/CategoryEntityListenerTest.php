<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Entity\EntityListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\EntityListener\CategoryEntityListener;
use Oro\Bundle\CatalogBundle\Manager\ProductIndexScheduler;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Cache\Adapter\AbstractAdapter;

class CategoryEntityListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var ProductIndexScheduler|\PHPUnit\Framework\MockObject\MockObject */
    private $productIndexScheduler;

    /** @var AbstractAdapter|\PHPUnit\Framework\MockObject\MockObject */
    private $categoryCache;

    /** @var CategoryEntityListener */
    private $listener;

    protected function setUp(): void
    {
        $this->productIndexScheduler = $this->createMock(ProductIndexScheduler::class);
        $this->categoryCache = $this->createMock(AbstractAdapter::class);

        $this->listener = new CategoryEntityListener(
            $this->productIndexScheduler,
            $this->categoryCache
        );
    }

    private function getCategoryAndSetSchedulerExpectation(): Category
    {
        $category = new Category();

        $this->productIndexScheduler->expects($this->once())
            ->method('scheduleProductsReindex')
            ->with([$category], null, true, ['main', 'inventory']);

        $this->categoryCache->expects($this->once())
            ->method('clear');

        return $category;
    }

    public function testPreRemove()
    {
        $category = $this->getCategoryAndSetSchedulerExpectation();
        $this->listener->preRemove($category);
    }

    public function testPostPersist()
    {
        $category = $this->getCategoryAndSetSchedulerExpectation();
        $this->listener->postPersist($category);
    }

    public function testPreUpdate()
    {
        $category = $this->getCategoryAndSetSchedulerExpectation();
        $emMock = $this->createMock(EntityManagerInterface::class);

        $changesSet = ['some_changes' => 1];
        $event = new PreUpdateEventArgs($category, $emMock, $changesSet);
        $this->listener->preUpdate($category, $event);
    }

    public function testPreUpdateNoChangesSet()
    {
        $category = new Category();
        $emMock = $this->createMock(EntityManagerInterface::class);

        $changesSet = [];
        $event = new PreUpdateEventArgs($category, $emMock, $changesSet);
        $this->productIndexScheduler->expects($this->never())
            ->method('scheduleProductsReindex');
        $this->listener->preUpdate($category, $event);
    }
}
