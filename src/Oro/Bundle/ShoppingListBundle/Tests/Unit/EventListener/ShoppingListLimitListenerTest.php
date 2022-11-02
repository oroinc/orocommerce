<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\CheckoutBundle\Event\LoginOnCheckoutEvent;
use Oro\Bundle\CheckoutBundle\Tests\Unit\Model\Action\CheckoutSourceStub;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\EventListener\ShoppingListLimitListener;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListLimitManager;
use Oro\Component\Testing\Unit\EntityTrait;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\Event;

class ShoppingListLimitListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var ShoppingListLimitManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $shoppingListLimitManager;

    /**
     * @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $doctrineHelper;

    /**
     * @var ShoppingListLimitListener
     */
    private $listener;

    /**
     * @var LoginOnCheckoutEvent|\PHPUnit\Framework\MockObject\MockObject
     */
    private $event;

    public static function setUpBeforeClass(): void
    {
        if (!class_exists('Oro\Bundle\CheckoutBundle\Entity\CheckoutSource')) {
            self::markTestSkipped('Should be tested only with CheckoutSource');
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->shoppingListLimitManager = $this->createMock(ShoppingListLimitManager::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->event = $this->createMock(LoginOnCheckoutEvent::class);

        $this->listener = new ShoppingListLimitListener(
            $this->shoppingListLimitManager,
            $this->doctrineHelper
        );
    }

    public function testLoginWithWrongEventClass()
    {
        $this->event = new Event();
        $this->shoppingListLimitManager->expects($this->never())
            ->method('isCreateEnabled');

        $this->listener->onCheckoutLogin($this->event);
    }

    public function testLoginWithoutReachedShoppingListLimit()
    {
        $this->shoppingListLimitManager->expects($this->once())
            ->method('isCreateEnabled')
            ->willReturn(true);
        $this->event->expects($this->never())
            ->method('getSource');

        $this->listener->onCheckoutLogin($this->event);
    }

    public function testLoginWithEmptySourceEntity()
    {
        $this->shoppingListLimitManager->expects($this->once())
            ->method('isCreateEnabled')
            ->willReturn(false);
        $this->event->expects($this->once())
            ->method('getSource')
            ->willReturn(new CheckoutSourceStub());
        $this->doctrineHelper->expects($this->never())
            ->method('getEntityManager');

        $this->listener->onCheckoutLogin($this->event);
    }

    public function testLoginWithException()
    {
        $source = new CheckoutSourceStub();
        $source->setShoppingList(new ShoppingList());

        $this->shoppingListLimitManager->expects($this->once())
            ->method('isCreateEnabled')
            ->willReturn(false);
        $this->event->expects($this->once())
            ->method('getSource')
            ->willReturn($source);

        $em = $this->createMock(EntityManager::class);
        $em->expects($this->once())
            ->method('transactional')
            ->willThrowException(new \Exception());
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($em);

        /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject $logger */
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error');
        $this->listener->setLogger($logger);

        $this->listener->onCheckoutLogin($this->event);
    }

    public function testLogin()
    {
        $source = new CheckoutSourceStub();
        $source->setShoppingList(new ShoppingList());

        $this->shoppingListLimitManager->expects($this->once())
            ->method('isCreateEnabled')
            ->willReturn(false);
        $this->event->expects($this->once())
            ->method('getSource')
            ->willReturn($source);

        $em = $this->createMock(EntityManager::class);
        $em->expects($this->once())
            ->method('transactional');

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($em);

        $this->listener->onCheckoutLogin($this->event);
    }
}
