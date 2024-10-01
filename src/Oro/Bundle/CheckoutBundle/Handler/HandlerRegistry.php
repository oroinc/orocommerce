<?php

namespace Oro\Bundle\CheckoutBundle\Handler;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Symfony\Component\HttpFoundation\Request;

/**
 * Registry of  checkout controller HTTP request handlers.
 */
class HandlerRegistry implements CheckoutHandlerInterface
{
    /**
     * @param iterable|CheckoutHandlerInterface[] $handlers
     */
    public function __construct(
        private iterable $handlers
    ) {
    }

    #[\Override]
    public function isSupported(Request $request): bool
    {
        return true;
    }

    #[\Override]
    public function handle(WorkflowItem $workflowItem, Request $request): void
    {
        foreach ($this->handlers as $handler) {
            if (!$handler->isSupported($request)) {
                continue;
            }

            $handler->handle($workflowItem, $request);
            break;
        }
    }
}
