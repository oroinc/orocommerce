<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Async;

use Doctrine\DBAL\Driver\DriverException;
use Doctrine\DBAL\Exception\DeadlockException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ShoppingListBundle\Async\InvalidateTotalsByInventoryStatusPerWebsiteProcessor;
use Oro\Bundle\ShoppingListBundle\Async\MessageFactory;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListTotalRepository;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingListTotal;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Provider\WebsiteProviderInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Oro\Component\Testing\Unit\EntityTrait;
use Psr\Log\LoggerInterface;

class InvalidateTotalsByInventoryStatusPerWebsiteProcessorTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configManager;

    /**
     * @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    private $registry;

    /**
     * @var MessageFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $messageFactory;

    /**
     * @var WebsiteProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $websiteProvider;

    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $logger;

    /**
     * @var InvalidateTotalsByInventoryStatusPerWebsiteProcessor
     */
    private $processor;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->messageFactory = $this->createMock(MessageFactory::class);
        $this->websiteProvider = $this->createMock(WebsiteProviderInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->processor = new InvalidateTotalsByInventoryStatusPerWebsiteProcessor(
            $this->configManager,
            $this->websiteProvider,
            $this->registry,
            $this->messageFactory,
            $this->logger
        );
    }

    public function testProcessGlobalNoWebsitesToProcess()
    {
        /** @var SessionInterface $session */
        $session = $this->createMock(SessionInterface::class);
        $data = [];
        $this->messageFactory->expects($this->once())
            ->method('getContext')
            ->with($data)
            ->willReturn(null);
        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message */
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn(JSON::encode($data));

        $website1 = $this->getEntity(Website::class, ['id' => 1]);
        $websites = [1 => $website1];
        $this->websiteProvider->expects($this->once())
            ->method('getWebsites')
            ->willReturn($websites);

        $this->configManager->expects($this->once())
            ->method('getValues')
            ->with(
                'oro_product.general_frontend_product_visibility',
                $websites,
                false,
                true
            )
            ->willReturn([
                1 => [
                    ConfigManager::USE_PARENT_SCOPE_VALUE_KEY => false,
                    ConfigManager::VALUE_KEY => ['in_stock']
                ]
            ]);

        $this->assertEquals(
            InvalidateTotalsByInventoryStatusPerWebsiteProcessor::ACK,
            $this->processor->process($message, $session)
        );
    }

    public function testProcessGlobal()
    {
        /** @var SessionInterface $session */
        $session = $this->createMock(SessionInterface::class);
        $data = [];
        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message */
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn(JSON::encode($data));

        /** @var Website $website1 */
        $website1 = $this->getEntity(Website::class, ['id' => 1]);
        $websites = [1 => $website1];
        $this->websiteProvider->expects($this->once())
            ->method('getWebsites')
            ->willReturn($websites);

        $allowedStatuses = ['in_stock'];
        $this->assertRepositoryCall($website1);

        $this->configManager->expects($this->once())
            ->method('getValues')
            ->with(
                'oro_product.general_frontend_product_visibility',
                $websites,
                false,
                true
            )
            ->willReturn([
                1 => [
                    ConfigManager::USE_PARENT_SCOPE_VALUE_KEY => true,
                    ConfigManager::VALUE_KEY => $allowedStatuses
                ]
            ]);

        $this->messageFactory->expects($this->once())
            ->method('getContext')
            ->with($data)
            ->willReturn(null);

        $this->assertEquals(
            InvalidateTotalsByInventoryStatusPerWebsiteProcessor::ACK,
            $this->processor->process($message, $session)
        );
    }

    public function testProcessWebsite()
    {
        /** @var SessionInterface $session */
        $session = $this->createMock(SessionInterface::class);
        $data = [
            'context' => [
                'class' => Website::class,
                'id' => 1
            ]
        ];
        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message */
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn(JSON::encode($data));

        /** @var Website $website1 */
        $website1 = $this->getEntity(Website::class, ['id' => 1]);
        $this->messageFactory->expects($this->once())
            ->method('getContext')
            ->with($data)
            ->willReturn($website1);
        $this->websiteProvider->expects($this->never())
            ->method('getWebsites');

        $this->assertRepositoryCall($website1);

        $this->assertEquals(
            InvalidateTotalsByInventoryStatusPerWebsiteProcessor::ACK,
            $this->processor->process($message, $session)
        );
    }

    public function testProcessRetryableException()
    {
        /** @var SessionInterface $session */
        $session = $this->createMock(SessionInterface::class);
        $data = [
            'context' => [
                'class' => Website::class,
                'id' => 1
            ]
        ];
        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message */
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn(JSON::encode($data));

        /** @var Website $website1 */
        $website1 = $this->getEntity(Website::class, ['id' => 1]);
        $this->messageFactory->expects($this->once())
            ->method('getContext')
            ->with($data)
            ->willReturn($website1);
        $this->websiteProvider->expects($this->never())
            ->method('getWebsites');

        /** @var DriverException $driverException */
        $driverException = $this->createMock(DriverException::class);
        $e = new DeadlockException('deadlock detected', $driverException);
        $repo = $this->createMock(ShoppingListTotalRepository::class);
        $repo->expects($this->once())
            ->method('invalidateByWebsite')
            ->willThrowException($e);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->with(ShoppingListTotal::class)
            ->willReturn($repo);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(ShoppingListTotal::class)
            ->willReturn($em);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Retryable database exception occurred during shopping list totals invalidation',
                ['exception' => $e]
            );

        $this->assertEquals(
            InvalidateTotalsByInventoryStatusPerWebsiteProcessor::REQUEUE,
            $this->processor->process($message, $session)
        );
    }

    private function assertRepositoryCall(Website $website): void
    {
        $repo = $this->createMock(ShoppingListTotalRepository::class);
        $repo->expects($this->once())
            ->method('invalidateByWebsite')
            ->with($website);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->with(ShoppingListTotal::class)
            ->willReturn($repo);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(ShoppingListTotal::class)
            ->willReturn($em);
    }
}
