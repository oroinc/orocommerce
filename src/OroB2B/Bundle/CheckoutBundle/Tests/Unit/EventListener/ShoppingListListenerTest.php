<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Bundle\CheckoutBundle\EventListener\ShoppingListListener;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

class ShoppingListListenerTest extends \PHPUnit_Framework_TestCase
{
    const CHECKOUT_CLASS_NAME = 'Oro\Bundle\CheckoutBundle\Entity\Checkout';
    const CHECKOUT_SOURCE_CLASS_NAME = 'Oro\Bundle\CheckoutBundle\Entity\CheckoutSource';

    use EntityTrait;

    /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    /** @var ObjectRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $checkoutRepository;

    /** @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $checkoutEntityManager;

    /** @var ObjectRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $checkoutSourceRepository;

    /** @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $checkoutSourceEntityManager;

    /** @var ShoppingListListener */
    protected $listener;

    protected function setUp()
    {
        $this->checkoutRepository = $this->getMock(ObjectRepository::class);
        $this->checkoutSourceRepository = $this->getMock(ObjectRepository::class);

        $this->checkoutEntityManager = $this->getMock(ObjectManager::class);
        $this->checkoutEntityManager->expects($this->any())
            ->method('getRepository')
            ->with(self::CHECKOUT_CLASS_NAME)
            ->willReturn($this->checkoutRepository);

        $this->checkoutSourceEntityManager = $this->getMock(ObjectManager::class);
        $this->checkoutSourceEntityManager->expects($this->any())
            ->method('getRepository')
            ->with(self::CHECKOUT_SOURCE_CLASS_NAME)
            ->willReturn($this->checkoutSourceRepository);

        $this->registry = $this->getMock(ManagerRegistry::class);
        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturnMap(
                [
                    [self::CHECKOUT_CLASS_NAME, $this->checkoutEntityManager],
                    [self::CHECKOUT_SOURCE_CLASS_NAME, $this->checkoutSourceEntityManager]
                ]
            );

        $this->listener = new ShoppingListListener(
            $this->registry,
            self::CHECKOUT_CLASS_NAME,
            self::CHECKOUT_SOURCE_CLASS_NAME
        );
    }

    public function testPreRemoveWithCheckoutSourceAndCheckout()
    {
        $entity = $this->getEntity(ShoppingList::class);
        $checkout = $this->getEntity(Checkout::class);
        $checkoutSource = $this->getEntity(CheckoutSource::class);

        $this->checkoutSourceRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['shoppingList' => $entity])
            ->willReturn($checkoutSource);

        $this->checkoutRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['source' => $checkoutSource])
            ->willReturn($checkout);

        $this->checkoutEntityManager->expects($this->once())->method('remove')->with($checkout);
        $this->checkoutEntityManager->expects($this->once())->method('flush')->with($checkout);

        $this->listener->preRemove($entity);
    }

    public function testPreRemoveWithCheckoutSourceAndWithoutCheckout()
    {
        $entity = $this->getEntity(ShoppingList::class);
        $checkoutSource = $this->getEntity(CheckoutSource::class);

        $this->checkoutSourceRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['shoppingList' => $entity])
            ->willReturn($checkoutSource);

        $this->checkoutRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['source' => $checkoutSource])
            ->willReturn(null);

        $this->checkoutEntityManager->expects($this->never())->method('remove');
        $this->checkoutEntityManager->expects($this->never())->method('flush');

        $this->listener->preRemove($entity);
    }

    public function testPreRemoveWithoutCheckoutSource()
    {
        $entity = $this->getEntity(ShoppingList::class);

        $this->checkoutSourceRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['shoppingList' => $entity])
            ->willReturn(null);

        $this->checkoutRepository->expects($this->never())->method('findOneBy');

        $this->checkoutEntityManager->expects($this->never())->method('remove');
        $this->checkoutEntityManager->expects($this->never())->method('flush');

        $this->listener->preRemove($entity);
    }
}
