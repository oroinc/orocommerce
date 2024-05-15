<?php

namespace Oro\Bundle\CheckoutBundle\Handler;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Symfony\Component\HttpFoundation\Request;

interface CheckoutHandlerInterface
{
    public function isSupported(Request $request): bool;

    public function handle(WorkflowItem $workflowItem, Request $request): void;
}
