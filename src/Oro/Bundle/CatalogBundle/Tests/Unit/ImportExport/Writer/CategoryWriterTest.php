<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\ImportExport\Writer;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\EventListener\TreeListener;
use Oro\Bundle\CatalogBundle\ImportExport\Writer\CategoryWriter;
use Oro\Bundle\ImportExportBundle\Tests\Unit\Writer\EntityWriterTest;

class CategoryWriterTest extends EntityWriterTest
{
    /** @var TreeListener|\PHPUnit\Framework\MockObject\MockObject */
    private $treeListener;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $categoryEntityManager;

    /** @var CategoryRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $categoryRepo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->treeListener = $this->createMock(TreeListener::class);

        $this->writer = new CategoryWriter(
            $this->doctrineHelper,
            $this->detachFixer,
            $this->contextRegistry,
            $this->treeListener
        );
    }

    /**
     * @dataProvider configurationProvider
     */
    public function testWrite(array $configuration): void
    {
        $this->mockDoctrineHelper();

        $this->treeListener->expects($this->exactly(2))
            ->method('setEnabled')
            ->withConsecutive([false], [true]);

        $this->categoryRepo->expects($this->once())
            ->method('recover');

        $this->categoryEntityManager->expects($this->once())
            ->method('flush');

        parent::testWrite($configuration);
    }

    public function testWriteException(): void
    {
        $this->treeListener->expects($this->exactly(2))
            ->method('setEnabled')
            ->withConsecutive([false], [true]);

        parent::testWriteException();
    }

    public function testWriteDatabaseExceptionDeadlock(): void
    {
        $this->mockDoctrineHelper();

        $this->treeListener->expects($this->exactly(2))
            ->method('setEnabled')
            ->withConsecutive([false], [true]);

        parent::testWriteDatabaseExceptionDeadlock();
    }

    public function testMissingClassName(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('entityName not resolved');

        $this->treeListener->expects($this->exactly(2))
            ->method('setEnabled')
            ->withConsecutive([false], [true]);

        parent::testMissingClassName();
    }

    public function testClassResolvedOnce(): void
    {
        $this->mockDoctrineHelper();

        $this->treeListener->expects($this->exactly(4))
            ->method('setEnabled')
            ->withConsecutive([false], [true], [false], [true]);

        parent::testClassResolvedOnce();
    }

    private function mockDoctrineHelper(): void
    {
        $this->categoryEntityManager = $this->createMock(EntityManager::class);
        $this->categoryRepo = $this->createMock(CategoryRepository::class);

        $this->doctrineHelper->expects($this->atLeastOnce())
            ->method('getEntityManagerForClass')
            ->with(Category::class)
            ->willReturn($this->categoryEntityManager);
        $this->doctrineHelper->expects($this->atLeastOnce())
            ->method('getEntityRepositoryForClass')
            ->with(Category::class)
            ->willReturn($this->categoryRepo);
    }
}
