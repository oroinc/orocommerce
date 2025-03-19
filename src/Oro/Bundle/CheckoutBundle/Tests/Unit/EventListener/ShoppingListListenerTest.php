<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Bundle\CheckoutBundle\EventListener\ShoppingListListener;
use Oro\Bundle\CheckoutBundle\Tests\Unit\Model\Action\CheckoutSourceStub;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Component\Testing\Unit\EntityTrait;

class ShoppingListListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;
    private const CHECKOUT_CLASS_NAME = Checkout::class;
    private const CHECKOUT_SOURCE_CLASS_NAME = CheckoutSource::class;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    protected $registry;

    /** @var ObjectRepository|\PHPUnit\Framework\MockObject\MockObject */
    protected $checkoutRepository;

    /** @var ObjectManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $checkoutEntityManager;

    /** @var ObjectRepository|\PHPUnit\Framework\MockObject\MockObject */
    protected $checkoutSourceRepository;

    /** @var ObjectManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $checkoutSourceEntityManager;

    /** @var ShoppingListListener */
    protected $listener;

    protected function setUp(): void
    {
        $this->checkoutRepository = $this->createMock(ObjectRepository::class);
        $this->checkoutSourceRepository = $this->createMock(ObjectRepository::class);

        $this->checkoutEntityManager = $this->createMock(ObjectManager::class);
        $this->checkoutEntityManager->expects($this->any())
            ->method('getRepository')
            ->with(self::CHECKOUT_CLASS_NAME)
            ->willReturn($this->checkoutRepository);

        $this->checkoutSourceEntityManager = $this->createMock(ObjectManager::class);
        $this->checkoutSourceEntityManager->expects($this->any())
            ->method('getRepository')
            ->with(self::CHECKOUT_SOURCE_CLASS_NAME)
            ->willReturn($this->checkoutSourceRepository);

        $this->registry = $this->createMock(ManagerRegistry::class);
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
        $checkoutSource1 = self::getEntity(CheckoutSourceStub::class, ['shopping_list' => $entity]);
        $checkoutSource2 = self::getEntity(CheckoutSourceStub::class, ['shopping_list' => $entity]);
        $checkout1 = self::getEntity(Checkout::class, ['source' => $checkoutSource1]);
        $checkout2 = self::getEntity(Checkout::class, ['source' => $checkoutSource2]);

        $this->checkoutSourceRepository->expects(self::once())
            ->method('findBy')
            ->with(['shoppingList' => $entity])
            ->willReturn([$checkoutSource1, $checkoutSource2]);

        $this->checkoutRepository->expects(self::once())
            ->method('findBy')
            ->with(['source' => [$checkoutSource1, $checkoutSource2]])
            ->willReturn([$checkout1, $checkout2]);

        $this->checkoutEntityManager->expects(self::exactly(2))
            ->method('remove')->withConsecutive([$checkout1], [$checkout2]);

        $this->checkoutEntityManager->expects(self::once())
            ->method('flush');

        $this->listener->preRemove($entity);

        self::assertEmpty($checkoutSource1->getShoppingList());
        self::assertEmpty($checkoutSource2->getShoppingList());
    }

    public function testPreRemoveWithCheckoutSourceAndWithoutCheckout()
    {
        $entity = $this->getEntity(ShoppingList::class);
        $checkoutSource = $this->getEntity(CheckoutSource::class);

        $this->checkoutSourceRepository->expects($this->once())
            ->method('findBy')
            ->with(['shoppingList' => $entity])
            ->willReturn([$checkoutSource]);

        $this->checkoutRepository->expects($this->once())
            ->method('findBy')
            ->with(['source' => [$checkoutSource]])
            ->willReturn([]);

        $this->checkoutEntityManager->expects($this->never())->method('remove');
        $this->checkoutEntityManager->expects($this->never())->method('flush');

        $this->listener->preRemove($entity);
    }

    public function testPreRemoveWithoutCheckoutSource()
    {
        $entity = $this->getEntity(ShoppingList::class);

        $this->checkoutSourceRepository->expects($this->once())
            ->method('findBy')
            ->with(['shoppingList' => $entity])
            ->willReturn([]);

        $this->checkoutRepository->expects($this->never())->method('findOneBy');

        $this->checkoutEntityManager->expects($this->never())->method('remove');
        $this->checkoutEntityManager->expects($this->never())->method('flush');

        $this->listener->preRemove($entity);
    }

    public function testPreRemoveWithCheckoutSourceAndCompletedCheckout()
    {
        $entity = $this->getEntity(ShoppingList::class);
        $checkoutSource = self::getEntity(CheckoutSourceStub::class, ['shopping_list' => $entity]);
        $checkout = self::getEntity(Checkout::class, ['completed' => true, 'source' => $checkoutSource]);

        $this->checkoutSourceRepository->expects(self::once())
            ->method('findBy')
            ->with(['shoppingList' => $entity])
            ->willReturn([$checkoutSource]);

        $this->checkoutRepository->expects(self::once())
            ->method('findBy')
            ->with(['source' => [$checkoutSource]])
            ->willReturn([$checkout]);

        $this->checkoutEntityManager->expects(self::never())->method('remove');
        $this->checkoutEntityManager->expects(self::never())->method('flush');

        $this->listener->preRemove($entity);

        self::assertEmpty($checkoutSource->getShoppingList());
    }
}
