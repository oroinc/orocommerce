<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Bundle\CheckoutBundle\EventListener\ShoppingListListener;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ShoppingListListenerTest extends TestCase
{
    use EntityTrait;

    private const string CHECKOUT_CLASS_NAME = Checkout::class;
    private const string CHECKOUT_SOURCE_CLASS_NAME = CheckoutSource::class;

    private ManagerRegistry&MockObject $registry;
    private ObjectRepository&MockObject $checkoutRepository;
    private ObjectRepository&MockObject $checkoutSourceRepository;
    private ObjectManager&MockObject $checkoutEntityManager;
    private ObjectManager&MockObject $checkoutSourceEntityManager;

    private ShoppingListListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->checkoutRepository = $this->createMock(ObjectRepository::class);
        $this->checkoutSourceRepository = $this->createMock(ObjectRepository::class);
        $this->checkoutEntityManager = $this->createMock(ObjectManager::class);
        $this->checkoutSourceEntityManager = $this->createMock(ObjectManager::class);

        $this->registry->expects(self::any())
            ->method('getRepository')
            ->withConsecutive([self::CHECKOUT_SOURCE_CLASS_NAME], [self::CHECKOUT_CLASS_NAME])
            ->willReturnOnConsecutiveCalls($this->checkoutSourceRepository, $this->checkoutRepository);

        $this->registry->expects(self::any())
            ->method('getManagerForClass')
            ->willReturnMap([
                [self::CHECKOUT_CLASS_NAME, $this->checkoutEntityManager],
                [self::CHECKOUT_SOURCE_CLASS_NAME, $this->checkoutSourceEntityManager]
            ]);

        $this->listener = new ShoppingListListener(
            $this->registry,
            self::CHECKOUT_CLASS_NAME,
            self::CHECKOUT_SOURCE_CLASS_NAME
        );
    }

    public function testPreRemoveWithCheckoutSourceAndCheckout(): void
    {
        $entity = $this->getEntity(ShoppingList::class);
        $checkout1 = $this->getEntity(Checkout::class);
        $checkout2 = $this->getEntity(Checkout::class);
        $checkoutSource1 = $this->getEntity(CheckoutSource::class);
        $checkoutSource2 = $this->getEntity(CheckoutSource::class);

        $this->checkoutSourceRepository->expects(self::once())
            ->method('findBy')
            ->with(['shoppingList' => $entity])
            ->willReturn([$checkoutSource1, $checkoutSource2]);

        $this->checkoutRepository->expects(self::once())
            ->method('findBy')
            ->with(['source' => [$checkoutSource1, $checkoutSource2]])
            ->willReturn([$checkout1, $checkout2]);

        $this->checkoutEntityManager->expects(self::exactly(2))
            ->method('remove')
            ->withConsecutive([$checkout1], [$checkout2]);

        $this->checkoutEntityManager->expects(self::once())
            ->method('flush');

        $this->listener->preRemove($entity);
    }

    public function testPreRemoveWithCheckoutSourceAndWithoutCheckout(): void
    {
        $entity = $this->getEntity(ShoppingList::class);
        $checkoutSource = $this->getEntity(CheckoutSource::class);

        $this->checkoutSourceRepository->expects(self::once())
            ->method('findBy')
            ->with(['shoppingList' => $entity])
            ->willReturn([$checkoutSource]);

        $this->checkoutRepository->expects(self::once())
            ->method('findBy')
            ->with(['source' => [$checkoutSource]])
            ->willReturn([]);

        $this->checkoutEntityManager->expects(self::never())->method('remove');
        $this->checkoutEntityManager->expects(self::never())->method('flush');

        $this->listener->preRemove($entity);
    }

    public function testPreRemoveWithoutCheckoutSource(): void
    {
        $entity = $this->getEntity(ShoppingList::class);

        $this->checkoutSourceRepository->expects(self::once())
            ->method('findBy')
            ->with(['shoppingList' => $entity])
            ->willReturn([]);

        $this->checkoutRepository->expects(self::never())->method('findOneBy');

        $this->checkoutEntityManager->expects(self::never())->method('remove');
        $this->checkoutEntityManager->expects(self::never())->method('flush');

        $this->listener->preRemove($entity);
    }

    public function testPreRemoveWithCheckoutSourceAndCompletedCheckout(): void
    {
        $entity = $this->getEntity(ShoppingList::class);
        $checkout = $this->getEntity(Checkout::class, ['completed' => true]);
        $checkoutSource = $this->getEntity(CheckoutSource::class);

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
    }
}
