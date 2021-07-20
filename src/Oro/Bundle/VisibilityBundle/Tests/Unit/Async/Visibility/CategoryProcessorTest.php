<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Async\Visibility;

use Doctrine\DBAL\Exception\DeadlockException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\VisibilityBundle\Async\Topics;
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
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

class CategoryProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var InsertFromSelectQueryExecutor|\PHPUnit\Framework\MockObject\MockObject */
    private $insertFromSelectQueryExecutor;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var CacheBuilder|\PHPUnit\Framework\MockObject\MockObject */
    private $cacheBuilder;

    /** @var ScopeManager|\PHPUnit\Framework\MockObject\MockObject */
    private $scopeManager;

    /** @var CategoryProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->insertFromSelectQueryExecutor = $this->createMock(InsertFromSelectQueryExecutor::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->cacheBuilder = $this->createMock(CacheBuilder::class);
        $this->scopeManager = $this->createMock(ScopeManager::class);

        $this->processor = new CategoryProcessor(
            $this->doctrine,
            $this->insertFromSelectQueryExecutor,
            $this->logger,
            $this->cacheBuilder,
            $this->scopeManager
        );
    }

    /**
     * @param mixed $body
     *
     * @return MessageInterface
     */
    private function getMessage($body): MessageInterface
    {
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->once())
            ->method('getBody')
            ->willReturn(JSON::encode($body));

        return $message;
    }

    private function getSession(): SessionInterface
    {
        return $this->createMock(SessionInterface::class);
    }

    public function testGetSubscribedTopics()
    {
        $this->assertEquals(
            [Topics::CATEGORY_POSITION_CHANGE, Topics::CATEGORY_REMOVE],
            CategoryProcessor::getSubscribedTopics()
        );
    }

    public function testProcessWithInvalidMessage()
    {
        $this->logger->expects($this->once())
            ->method('critical')
            ->with('Got invalid message.');

        $this->assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($this->getMessage('invalid'), $this->getSession())
        );
    }

    public function testProcess()
    {
        $body = ['id' => 42];

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects(($this->never()))
            ->method('rollback');
        $em->expects(($this->once()))
            ->method('commit');

        $this->doctrine->expects($this->exactly(2))
            ->method('getManagerForClass')
            ->willReturnMap([
                [CategoryVisibilityResolved::class, $em],
                [Category::class, $em]
            ]);

        $category = new Category();
        $em->expects($this->once())
            ->method('find')
            ->with(Category::class, $body['id'])
            ->willReturn($category);
        $this->cacheBuilder->expects($this->once())
            ->method('categoryPositionChanged')
            ->with($category);

        $this->assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }

    public function testProcessWithoutCategory()
    {
        $body = [];

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects(($this->never()))
            ->method('rollback');
        $em->expects(($this->once()))
            ->method('commit');

        $this->scopeManager->expects($this->once())
            ->method('findRelatedScopes')
            ->with(ProductVisibility::VISIBILITY_TYPE)
            ->willReturn(new \ArrayIterator([]));

        $productVisibilityRepository = $this->createMock(ProductVisibilityRepository::class);
        $productVisibilityRepository->expects($this->any())
            ->method('setToDefaultWithoutCategory')
            ->with($this->insertFromSelectQueryExecutor, $this->scopeManager);

        $customerGroupProductVisibilityRepository = $this->createMock(CustomerGroupProductVisibilityRepository::class);
        $customerGroupProductVisibilityRepository->expects($this->once())
            ->method('setToDefaultWithoutCategory');

        $customerProductVisibilityRepository = $this->createMock(CustomerProductVisibilityRepository::class);
        $customerProductVisibilityRepository->expects($this->once())
            ->method('setToDefaultWithoutCategory');

        $em->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                [ProductVisibility::class, $productVisibilityRepository],
                [CustomerGroupProductVisibility::class, $customerGroupProductVisibilityRepository],
                [CustomerProductVisibility::class, $customerProductVisibilityRepository]
            ]);

        $this->doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);

        $this->assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }

    public function testProcessDeadlock()
    {
        $body = ['id' => 42];

        $exception = $this->createMock(DeadlockException::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects(($this->once()))
            ->method('rollback');
        $em->expects(($this->never()))
            ->method('commit');

        $this->doctrine->expects($this->exactly(2))
            ->method('getManagerForClass')
            ->willReturnMap([
                [CategoryVisibilityResolved::class, $em],
                [Category::class, $em]
            ]);

        $category = new Category();
        $em->expects($this->once())
            ->method('find')
            ->with(Category::class, $body['id'])
            ->willReturn($category);
        $this->cacheBuilder->expects($this->once())
            ->method('categoryPositionChanged')
            ->with($category)
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during update Category Visibility.',
                ['exception' => $exception]
            );

        $this->assertEquals(
            MessageProcessorInterface::REQUEUE,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }

    public function testProcessException()
    {
        $body = ['id' => 42];

        $exception = new \Exception('Some error');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects(($this->once()))
            ->method('rollback');
        $em->expects(($this->never()))
            ->method('commit');

        $this->doctrine->expects($this->exactly(2))
            ->method('getManagerForClass')
            ->willReturnMap([
                [CategoryVisibilityResolved::class, $em],
                [Category::class, $em]
            ]);

        $category = new Category();
        $em->expects($this->once())
            ->method('find')
            ->with(Category::class, $body['id'])
            ->willReturn($category);
        $this->cacheBuilder->expects($this->once())
            ->method('categoryPositionChanged')
            ->with($category)
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during update Category Visibility.',
                ['exception' => $exception]
            );

        $this->assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }

    public function testProcessWhenCategoryNotFound()
    {
        $body = ['id' => 42];

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects(($this->once()))
            ->method('rollback');
        $em->expects(($this->never()))
            ->method('commit');

        $this->doctrine->expects($this->exactly(2))
            ->method('getManagerForClass')
            ->willReturnMap([
                [CategoryVisibilityResolved::class, $em],
                [Category::class, $em]
            ]);

        $em->expects($this->once())
            ->method('find')
            ->with(Category::class, $body['id'])
            ->willReturn(null);
        $this->cacheBuilder->expects($this->never())
            ->method('categoryPositionChanged');

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during update Category Visibility.',
                ['exception' => new EntityNotFoundException('Category was not found.')]
            );

        $this->assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }
}
