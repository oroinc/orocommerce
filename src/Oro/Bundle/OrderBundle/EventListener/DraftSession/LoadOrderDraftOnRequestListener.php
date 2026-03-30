<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\EventListener\DraftSession;

use Oro\Bundle\OrderBundle\DraftSession\Manager\OrderDraftManager;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Loads the order draft entity if the draft session UUID is present in the request context.
 * Ensures ACL check on the order draft entity based on the draft session UUID.
 * Sets the order draft entity to the request attributes for further usage in the request lifecycle.
 */
class LoadOrderDraftOnRequestListener
{
    public const string ORDER_DRAFT = 'orderDraft';

    public function __construct(
        private readonly OrderDraftManager $orderDraftManager,
        private readonly AuthorizationCheckerInterface $authorizationChecker
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $draftSessionUuid = $this->orderDraftManager->getDraftSessionUuid();
        if (!$draftSessionUuid) {
            return;
        }

        $orderDraft = $this->orderDraftManager->findOrderDraft($draftSessionUuid);
        if ($orderDraft !== null && !$this->authorizationChecker->isGranted('oro_order_update', $orderDraft)) {
            throw new AccessDeniedHttpException(
                'Access denied to the order draft entity with UUID ' . $draftSessionUuid
            );
        }

        $event->getRequest()->attributes->set(self::ORDER_DRAFT, $orderDraft);
    }
}
