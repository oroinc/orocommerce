<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Async\Visibility;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Exception\DeadlockException;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Model\Exception\InvalidArgumentException;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\VisibilityBundle\Async\Visibility\CategoryProcessor;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\Repository\CustomerGroupProductVisibilityRepository;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\Repository\CustomerProductVisibilityRepository;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\Repository\ProductVisibilityRepository;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Model\CategoryMessageFactory;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\Product\Category\CacheBuilder;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class CategoryProcessorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $registry;

    /**
     * @var InsertFromSelectQueryExecutor|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $insertFromSelectQueryExecutor;

    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $logger;

    /**
     * @var CategoryMessageFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $messageFactory;

    /**
     * @var CacheBuilder|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $cacheBuilder;

    /**
     * @var CategoryProcessor
     */
    protected $categoryProcessor;

    /**
     * @var ScopeManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $scopeManager;

    protected function setUp()
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->insertFromSelectQueryExecutor = $this->getMockBuilder(InsertFromSelectQueryExecutor::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->messageFactory = $this->getMockBuilder(CategoryMessageFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->cacheBuilder = $this->createMock(CacheBuilder::class);
        $this->scopeManager = $this->getMockBuilder(ScopeManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->categoryProcessor = new CategoryProcessor(
            $this->registry,
            $this->insertFromSelectQueryExecutor,
            $this->logger,
            $this->messageFactory,
            $this->cacheBuilder,
            $this->scopeManager
        );
    }

    public function testProcessWithCategory()
    {
        $data = ['test' => 42];
        $body = json_encode($data);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects(($this->never()))
            ->method('rollback');
        $em->expects(($this->once()))
            ->method('commit');
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(CategoryVisibilityResolved::class)
            ->willReturn($em);
        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message * */
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn($body);
        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session * */
        $session = $this->createMock(SessionInterface::class);
        $category = new Category();
        $this->cacheBuilder->expects($this->once())
            ->method('categoryPositionChanged')
            ->with($category);
        $this->messageFactory->expects($this->once())
            ->method('getCategoryFromMessage')
            ->with($data)
            ->willReturn($category);
        $this->assertEquals(
            MessageProcessorInterface::ACK,
            $this->categoryProcessor->process($message, $session)
        );
    }

    public function testProcessWithoutCategory()
    {
        $data = ['test' => 42];
        $body = json_encode($data);

        $scope = $this->getMockBuilder(Scope::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->scopeManager->expects($this->once())
            ->method('findRelatedScopes')
            ->with(ProductVisibility::VISIBILITY_TYPE)
            ->willReturn($scope);

        $productVisibilityRepository = $this->getMockBuilder(ProductVisibilityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $productVisibilityRepository->expects($this->any())
            ->method('setToDefaultWithoutCategory')
            ->with($this->insertFromSelectQueryExecutor, $this->scopeManager);

        $customerGroupProductVisibilityRepository = $this->getMockBuilder(
            CustomerGroupProductVisibilityRepository::class
        )
            ->disableOriginalConstructor()
            ->getMock();

        $customerGroupProductVisibilityRepository->expects($this->once())
            ->method('setToDefaultWithoutCategory');

        $customerProductVisibilityRepository = $this->getMockBuilder(CustomerProductVisibilityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $customerProductVisibilityRepository->expects($this->once())
            ->method('setToDefaultWithoutCategory');

        $em = $this->getMockBuilder(EntityManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $em->expects($this->once())
            ->method('beginTransaction');

        $em->expects(($this->never()))
            ->method('rollback');

        $em->expects(($this->once()))
            ->method('commit');

        $em->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                [ProductVisibility::class, $productVisibilityRepository],
                [CustomerGroupProductVisibility::class, $customerGroupProductVisibilityRepository],
                [CustomerProductVisibility::class, $customerProductVisibilityRepository]
            ]);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);

        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message **/
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn($body);

        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session **/
        $session = $this->createMock(SessionInterface::class);

        $this->messageFactory->expects($this->once())
            ->method('getCategoryFromMessage')
            ->with($data)
            ->willReturn(null);

        $this->assertEquals(
            MessageProcessorInterface::ACK,
            $this->categoryProcessor->process($message, $session)
        );
    }

    public function testProcessDeadlock()
    {
        /** @var DeadlockException $exception */
        $exception = $this->createMock(DeadlockException::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects(($this->once()))
            ->method('rollback');

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(CategoryVisibilityResolved::class)
            ->willReturn($em);

        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message * */
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->will($this->throwException($exception));
        $this->logger->expects($this->once())
            ->method('error')
            ->with('Unexpected exception occurred during Category visibility resolve', ['exception' => $exception]);

        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session * */
        $session = $this->createMock(SessionInterface::class);

        $this->assertEquals(
            MessageProcessorInterface::REQUEUE,
            $this->categoryProcessor->process($message, $session)
        );
    }

    public function testProcessException()
    {
        $exception = new \Exception('Some error');
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects(($this->once()))
            ->method('rollback');
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(CategoryVisibilityResolved::class)
            ->willReturn($em);
        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message * */
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->will($this->throwException($exception));
        $this->logger->expects($this->once())
            ->method('error')
            ->with('Unexpected exception occurred during Category visibility resolve', ['exception' => $exception]);
        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session * */
        $session = $this->createMock(SessionInterface::class);
        $this->assertEquals(
            MessageProcessorInterface::REJECT,
            $this->categoryProcessor->process($message, $session)
        );
    }

    public function testProcessReject()
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects(($this->once()))
            ->method('rollback');
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(CategoryVisibilityResolved::class)
            ->willReturn($em);
        $this->messageFactory->expects($this->once())
            ->method('getCategoryFromMessage')
            ->will($this->throwException(new InvalidArgumentException('Wrong message')));
        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message * */
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn(json_encode([]));
        $this->logger->expects($this->once())
            ->method('error')
            ->with('Message is invalid: Wrong message');
        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session * */
        $session = $this->createMock(SessionInterface::class);
        $this->assertEquals(
            MessageProcessorInterface::REJECT,
            $this->categoryProcessor->process($message, $session)
        );
    }
}
