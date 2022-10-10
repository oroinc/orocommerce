<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Async\Visibility;

use Doctrine\DBAL\Exception\DeadlockException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Oro\Bundle\VisibilityBundle\Async\Topic\VisibilityOnChangeCategoryPositionTopic;
use Oro\Bundle\VisibilityBundle\Async\Topic\VisibilityOnRemoveCategoryTopic;
use Oro\Bundle\VisibilityBundle\Async\Visibility\CategoryProcessor;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\Repository\CustomerGroupProductVisibilityRepository;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\Repository\CustomerProductVisibilityRepository;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\Repository\ProductVisibilityRepository;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\Product\Category\CacheBuilder;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

class CategoryProcessorTest extends \PHPUnit\Framework\TestCase
{
    use LoggerAwareTraitTestTrait;

    private ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject $doctrine;

    private InsertFromSelectQueryExecutor|\PHPUnit\Framework\MockObject\MockObject $insertFromSelectQueryExecutor;

    private CacheBuilder|\PHPUnit\Framework\MockObject\MockObject $cacheBuilder;

    private ScopeManager|\PHPUnit\Framework\MockObject\MockObject $scopeManager;

    private CategoryProcessor $processor;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->insertFromSelectQueryExecutor = $this->createMock(InsertFromSelectQueryExecutor::class);
        $this->cacheBuilder = $this->createMock(CacheBuilder::class);
        $this->scopeManager = $this->createMock(ScopeManager::class);

        $this->processor = new CategoryProcessor(
            $this->doctrine,
            $this->insertFromSelectQueryExecutor,
            $this->cacheBuilder,
            $this->scopeManager
        );
        $this->setUpLoggerMock($this->processor);
    }

    private function getMessage(array $body): MessageInterface
    {
        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::once())
            ->method('getBody')
            ->willReturn($body);

        return $message;
    }

    private function getSession(): SessionInterface
    {
        return $this->createMock(SessionInterface::class);
    }

    public function testGetSubscribedTopics(): void
    {
        self::assertEquals(
            [VisibilityOnChangeCategoryPositionTopic::getName(), VisibilityOnRemoveCategoryTopic::getName()],
            CategoryProcessor::getSubscribedTopics()
        );
    }

    public function testProcess(): void
    {
        $body = ['id' => 42];

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('beginTransaction');
        $em->expects((self::never()))
            ->method('rollback');
        $em->expects((self::once()))
            ->method('commit');

        $this->doctrine->expects(self::exactly(2))
            ->method('getManagerForClass')
            ->willReturnMap([
                [CategoryVisibilityResolved::class, $em],
                [Category::class, $em],
            ]);

        $category = new Category();
        $em->expects(self::once())
            ->method('find')
            ->with(Category::class, $body['id'])
            ->willReturn($category);
        $this->cacheBuilder->expects(self::once())
            ->method('categoryPositionChanged')
            ->with($category);

        self::assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }

    public function testProcessWithoutCategory(): void
    {
        $body = [];

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('beginTransaction');
        $em->expects((self::never()))
            ->method('rollback');
        $em->expects((self::once()))
            ->method('commit');

        $this->scopeManager->expects(self::once())
            ->method('findRelatedScopes')
            ->with(ProductVisibility::VISIBILITY_TYPE)
            ->willReturn(new \ArrayIterator([]));

        $productVisibilityRepository = $this->createMock(ProductVisibilityRepository::class);
        $productVisibilityRepository->expects(self::any())
            ->method('setToDefaultWithoutCategory')
            ->with($this->insertFromSelectQueryExecutor, $this->scopeManager);

        $customerGroupProductVisibilityRepository = $this->createMock(CustomerGroupProductVisibilityRepository::class);
        $customerGroupProductVisibilityRepository->expects(self::once())
            ->method('setToDefaultWithoutCategory');

        $customerProductVisibilityRepository = $this->createMock(CustomerProductVisibilityRepository::class);
        $customerProductVisibilityRepository->expects(self::once())
            ->method('setToDefaultWithoutCategory');

        $this->doctrine
            ->expects(self::any())
            ->method('getRepository')
            ->willReturnMap([
                [ProductVisibility::class, null, $productVisibilityRepository],
                [CustomerGroupProductVisibility::class, null, $customerGroupProductVisibilityRepository],
                [CustomerProductVisibility::class, null, $customerProductVisibilityRepository],
            ]);

        $this->doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($em);

        self::assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }

    public function testProcessDeadlock(): void
    {
        $body = ['id' => 42];

        $exception = $this->createMock(DeadlockException::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('beginTransaction');
        $em->expects((self::once()))
            ->method('rollback');
        $em->expects((self::never()))
            ->method('commit');

        $this->doctrine->expects(self::exactly(2))
            ->method('getManagerForClass')
            ->willReturnMap([
                [CategoryVisibilityResolved::class, $em],
                [Category::class, $em],
            ]);

        $category = new Category();
        $em->expects(self::once())
            ->method('find')
            ->with(Category::class, $body['id'])
            ->willReturn($category);
        $this->cacheBuilder->expects(self::once())
            ->method('categoryPositionChanged')
            ->with($category)
            ->willThrowException($exception);

        $this->loggerMock->expects(self::once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during update Category Visibility.',
                ['exception' => $exception]
            );

        self::assertEquals(
            MessageProcessorInterface::REQUEUE,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }

    public function testProcessException(): void
    {
        $body = ['id' => 42];

        $exception = new \Exception('Some error');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('beginTransaction');
        $em->expects((self::once()))
            ->method('rollback');
        $em->expects((self::never()))
            ->method('commit');

        $this->doctrine->expects(self::exactly(2))
            ->method('getManagerForClass')
            ->willReturnMap([
                [CategoryVisibilityResolved::class, $em],
                [Category::class, $em],
            ]);

        $category = new Category();
        $em->expects(self::once())
            ->method('find')
            ->with(Category::class, $body['id'])
            ->willReturn($category);
        $this->cacheBuilder->expects(self::once())
            ->method('categoryPositionChanged')
            ->with($category)
            ->willThrowException($exception);

        $this->loggerMock->expects(self::once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during update Category Visibility.',
                ['exception' => $exception]
            );

        self::assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }

    public function testProcessWhenCategoryNotFound(): void
    {
        $body = ['id' => 42];

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('beginTransaction');
        $em->expects((self::once()))
            ->method('rollback');
        $em->expects((self::never()))
            ->method('commit');

        $this->doctrine->expects(self::exactly(2))
            ->method('getManagerForClass')
            ->willReturnMap([
                [CategoryVisibilityResolved::class, $em],
                [Category::class, $em],
            ]);

        $em->expects(self::once())
            ->method('find')
            ->with(Category::class, $body['id'])
            ->willReturn(null);
        $this->cacheBuilder->expects(self::never())
            ->method('categoryPositionChanged');

        $this->loggerMock->expects(self::once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during update Category Visibility.',
                ['exception' => new EntityNotFoundException('Category was not found.')]
            );

        self::assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }
}
