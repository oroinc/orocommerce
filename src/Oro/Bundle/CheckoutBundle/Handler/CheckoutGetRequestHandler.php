<?php

namespace Oro\Bundle\CheckoutBundle\Handler;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Symfony\Component\HttpFoundation\Request;

/**
 * Handles checkout controller HTTP GET request.
 */
class CheckoutGetRequestHandler implements CheckoutHandlerInterface
{
    public function __construct(
        private WorkflowManager $workflowManager
    ) {
    }

    #[\Override]
    public function isSupported(Request $request): bool
    {
        return $request->isMethod(Request::METHOD_GET);
    }

    #[\Override]
    public function handle(WorkflowItem $workflowItem, Request $request): void
    {
        if (!$request->query->has('transition')) {
            return;
        }

        $transition = $request->query->get('transition');
        if ($transition === 'payment_error' && $request->query->has('layout_block_ids')) {
            // Do not transit workflow if requested only layout updates
            return;
        }

        $this->workflowManager->transitIfAllowed($workflowItem, $transition);
    }
}
