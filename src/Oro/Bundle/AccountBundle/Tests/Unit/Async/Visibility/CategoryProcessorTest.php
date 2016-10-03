<?php

namespace Oro\Bundle\AccountBundle\Tests\Unit\Async\Visibility;

use Doctrine\Common\Persistence\ManagerRegistry;

use Doctrine\DBAL\Driver\PDOException;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\AccountBundle\Entity\Visibility\AccountGroupProductVisibility;
use Oro\Bundle\AccountBundle\Entity\Visibility\AccountProductVisibility;
use Oro\Bundle\AccountBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\AccountBundle\Entity\Visibility\Repository\AccountGroupProductVisibilityRepository;
use Oro\Bundle\AccountBundle\Entity\Visibility\Repository\AccountProductVisibilityRepository;
use Oro\Bundle\AccountBundle\Entity\Visibility\Repository\ProductVisibilityRepository;
use Oro\Bundle\AccountBundle\Entity\VisibilityResolved\CategoryVisibilityResolved;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Model\Exception\InvalidArgumentException;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\CatalogBundle\Model\CategoryMessageFactory;
use Oro\Bundle\AccountBundle\Visibility\Cache\Product\Category\CacheBuilder;
use Oro\Bundle\EntityBundle\ORM\DatabaseExceptionHelper;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Bundle\AccountBundle\Async\Visibility\CategoryProcessor;

use Psr\Log\LoggerInterface;

class CategoryProcessorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var InsertFromSelectQueryExecutor|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $insertFromSelectQueryExecutor;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $logger;

    /**
     * @var CategoryMessageFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $messageFactory;

    /**
     * @var CacheBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $cacheBuilder;

    /**
     * @var DatabaseExceptionHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $databaseExceptionHelper;

    /**
     * @var CategoryProcessor
     */
    protected $categoryProcessor;

    protected function setUp()
    {
        $this->registry = $this->getMock(ManagerRegistry::class);
        $this->insertFromSelectQueryExecutor = $this->getMockBuilder(InsertFromSelectQueryExecutor::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->logger = $this->getMock(LoggerInterface::class);
        $this->messageFactory = $this->getMockBuilder(CategoryMessageFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->cacheBuilder = $this->getMock(CacheBuilder::class);
        $this->databaseExceptionHelper = $this->getMockBuilder(DatabaseExceptionHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->categoryProcessor = new CategoryProcessor(
            $this->registry,
            $this->insertFromSelectQueryExecutor,
            $this->logger,
            $this->messageFactory,
            $this->cacheBuilder,
            $this->databaseExceptionHelper
        );
    }

    public function testProcessWithCategory()
    {
        $data = ['test' => 42];
        $body = json_encode($data);

        $em = $this->getMock(EntityManagerInterface::class);

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

        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message **/
        $message = $this->getMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn($body);

        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session **/
        $session = $this->getMock(SessionInterface::class);

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

        $productVisibilityRepository = $this->getMockBuilder(ProductVisibilityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $productVisibilityRepository->expects($this->once())
            ->method('setToDefaultWithoutCategory')
            ->with($this->insertFromSelectQueryExecutor);

        $accountGroupProductVisibilityRepository = $this->getMockBuilder(AccountGroupProductVisibilityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $accountGroupProductVisibilityRepository->expects($this->once())
            ->method('setToDefaultWithoutCategory');

        $accountProductVisibilityRepository = $this->getMockBuilder(AccountProductVisibilityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $accountProductVisibilityRepository->expects($this->once())
            ->method('setToDefaultWithoutCategory');

        $em = $this->getMock(EntityManagerInterface::class);

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
                [AccountGroupProductVisibility::class, $accountGroupProductVisibilityRepository],
                [AccountProductVisibility::class, $accountProductVisibilityRepository]
            ]);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);

        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message **/
        $message = $this->getMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn($body);

        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session **/
        $session = $this->getMock(SessionInterface::class);

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
        /** @var PDOException $exception */
        $exception = $this->getMockBuilder(PDOException::class)
            ->disableOriginalConstructor()
            ->getMock();

        $em = $this->getMock(EntityManagerInterface::class);

        $em->expects($this->once())
            ->method('beginTransaction');

        $em->expects(($this->once()))
            ->method('rollback');

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(CategoryVisibilityResolved::class)
            ->willReturn($em);

        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message **/
        $message = $this->getMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->will($this->throwException($exception));

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Unexpected exception occurred during Category visibility resolve', ['exception' => $exception]);

        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session **/
        $session = $this->getMock(SessionInterface::class);

        $this->databaseExceptionHelper->expects($this->once())
            ->method('isDeadlock')
            ->willReturn(true);

        $this->assertEquals(
            MessageProcessorInterface::REQUEUE,
            $this->categoryProcessor->process($message, $session)
        );
    }

    public function testProcessException()
    {
        $exception = new \Exception('Some error');

        $em = $this->getMock(EntityManagerInterface::class);

        $em->expects($this->once())
            ->method('beginTransaction');

        $em->expects(($this->once()))
            ->method('rollback');

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(CategoryVisibilityResolved::class)
            ->willReturn($em);

        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message **/
        $message = $this->getMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->will($this->throwException($exception));

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Unexpected exception occurred during Category visibility resolve', ['exception' => $exception]);

        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session **/
        $session = $this->getMock(SessionInterface::class);

        $this->databaseExceptionHelper->expects($this->never())
            ->method('isDeadlock');

        $this->assertEquals(
            MessageProcessorInterface::REJECT,
            $this->categoryProcessor->process($message, $session)
        );
    }

    public function testProcessReject()
    {
        $em = $this->getMock(EntityManagerInterface::class);

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

        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message **/
        $message = $this->getMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn(json_encode([]));

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Message is invalid: Wrong message. Original message: "[]"');

        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session **/
        $session = $this->getMock(SessionInterface::class);

        $this->assertEquals(
            MessageProcessorInterface::REJECT,
            $this->categoryProcessor->process($message, $session)
        );
    }
}
