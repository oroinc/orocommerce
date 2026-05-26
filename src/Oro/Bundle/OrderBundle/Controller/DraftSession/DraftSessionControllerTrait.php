<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Controller\DraftSession;

use Oro\Bundle\OrderBundle\DraftSession\Manager\OrderDraftManager;
use Oro\Bundle\OrderBundle\Entity\Order;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Provides helper methods for controllers that work with draft sessions.
 *
 * Requires {@see OrderDraftManager} to be registered in the controller's service locator
 * via {@see getSubscribedServices()}.
 */
trait DraftSessionControllerTrait
{
    private function getOrderDraftManager(): OrderDraftManager
    {
        $orderDraftManager = $this->container->get(OrderDraftManager::class);
        assert($orderDraftManager instanceof OrderDraftManager);

        return $orderDraftManager;
    }

    /**
     * Asserts that the given order has a draft and the current user has access to it.
     */
    private function assertOrderDraftExists(?Order $order): void
    {
        if ($order === null) {
            throw $this->createNotFoundException();
        }

        $orderDraft = $this->getOrderDraftManager()->findEntityDraft($order);
        if ($orderDraft === null) {
            throw $this->createNotFoundException(
                'Draft entity of the order #' . $order->getId() . ' is not found.'
            );
        }

        /** @var AuthorizationCheckerInterface $authorizationChecker */
        $authorizationChecker = $this->container->get(AuthorizationCheckerInterface::class);

        if (!$authorizationChecker->isGranted('oro_order_update', $orderDraft)) {
            throw $this->createAccessDeniedException(
                'Access denied to the draft entity of order #' . $order->getId()
            );
        }
    }

    /**
     * Asserts that the current user has access to the draft of the given order if it exists.
     */
    private function assertOrderDraftIsGranted(Order $order): void
    {
        $orderDraft = $this->getOrderDraftManager()->findEntityDraft($order);
        if ($orderDraft === null) {
            // Order draft does not exist.
            return;
        }

        /** @var AuthorizationCheckerInterface $authorizationChecker */
        $authorizationChecker = $this->container->get(AuthorizationCheckerInterface::class);

        if (!$authorizationChecker->isGranted('oro_order_update', $orderDraft)) {
            throw $this->createAccessDeniedException(
                'Access denied to the draft entity of order #' . $order->getId()
            );
        }
    }
}
