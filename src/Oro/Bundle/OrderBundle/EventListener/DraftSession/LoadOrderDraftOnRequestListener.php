<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\EventListener\DraftSession;

use Oro\Bundle\OrderBundle\DraftSession\Manager\OrderDraftManager;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Loads the order draft entity if the draft session UUID is present in the request context.
 * Ensures ACL check on the order draft entity based on the draft session UUID.
 * Sets the order draft entity to the request attributes for further usage in the request lifecycle.
 *
 * @bc-layer This class is retained for BC reasons. It won't have any replacement.
 */
class LoadOrderDraftOnRequestListener
{
    public const string ORDER_DRAFT = 'orderDraft';

    public function __construct(
        private readonly OrderDraftManager $orderDraftManager,
        private readonly AuthorizationCheckerInterface $authorizationChecker
    ) {
    }

    /**
     * @bc-layer This method is retained for BC reasons. It won't have any replacement.
     */
    public function onKernelRequest(RequestEvent $event): void
    {
    }
}
