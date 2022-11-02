<?php

namespace Oro\Bundle\ShoppingListBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Bundle\CheckoutBundle\Event\LoginOnCheckoutEvent;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListLimitManager;
use Psr\Log\LoggerAwareTrait;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Shopping list should be removed when limit by shopping list is reached
 */
class ShoppingListLimitListener
{
    use LoggerAwareTrait;

    /**
     * @var ShoppingListLimitManager
     */
    private $shoppingListLimitManager;

    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    public function __construct(
        ShoppingListLimitManager $shoppingListLimitManager,
        DoctrineHelper $doctrineHelper
    ) {
        $this->shoppingListLimitManager = $shoppingListLimitManager;
        $this->doctrineHelper           = $doctrineHelper;
    }

    public function onCheckoutLogin(Event $event)
    {
        if (!class_exists(CheckoutSource::class)
            || !$event instanceof LoginOnCheckoutEvent) {
            return;
        }
        if ($this->shoppingListLimitManager->isCreateEnabled()) {
            return;
        }

        $source = $event->getSource();
        $sourceEntity = $source->getEntity();
        if (!$sourceEntity instanceof ShoppingList) {
            return;
        }

        try {
            $em = $this->getEntityManager(ShoppingList::class);
            $em->transactional(function ($em) use ($source, $sourceEntity) {
                /** @noinspection PhpUndefinedMethodInspection - field added through entity extend */
                $source->setShoppingList(null);
                /** Flush is required because the checkout should be left */
                $this->getEntityManager(CheckoutSource::class)->flush($source);
                /** @var EntityManager $em */
                $em->remove($sourceEntity);
            });
        } catch (\Exception $e) {
            if (null !== $this->logger) {
                $this->logger->error('Unable to remove guest shopping list', ['exception'=> $e]);
            }
        }
    }

    /**
     * @param string $className
     *
     * @return EntityManager
     */
    private function getEntityManager($className)
    {
        return $this->doctrineHelper->getEntityManager($className);
    }
}
