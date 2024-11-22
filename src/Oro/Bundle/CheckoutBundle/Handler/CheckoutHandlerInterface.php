<?php

namespace Oro\Bundle\CheckoutBundle\Handler;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Symfony\Component\HttpFoundation\Request;

/**
 * Handles checkout controller HTTP requests.
 */
interface CheckoutHandlerInterface
{
    public function isSupported(Request $request): bool;

    public function handle(WorkflowItem $workflowItem, Request $request): void;
}
