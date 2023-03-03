<?php

namespace Oro\Bundle\CatalogBundle\EventListener;

use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\UIBundle\Route\Router as UiRouter;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Seeks for "sortOrderDialogTarget" in input action parameter of a request and stores it in a session.
 */
class SortOrderDialogTriggerFormHandlerEventListener
{
    public const SORT_ORDER_DIALOG_TARGET = 'sortOrderDialogTarget';

    private RequestStack $requestStack;

    private UiRouter $uiRouter;

    public function __construct(RequestStack $requestStack, UiRouter $uiRouter)
    {
        $this->requestStack = $requestStack;
        $this->uiRouter = $uiRouter;
    }

    public function onFormAfterFlush(AfterFormProcessEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request === null) {
            return;
        }

        try {
            $inputActionData = (array)$this->uiRouter->getInputActionData($request);
        } catch (\InvalidArgumentException $exception) {
            // UI router failed to parse input action data.
            $inputActionData = [];
        }

        $sortOrderDialogTarget = $inputActionData[self::SORT_ORDER_DIALOG_TARGET] ?? null;
        if (is_string($sortOrderDialogTarget) && $request->hasSession()) {
            $request->getSession()->set(self::SORT_ORDER_DIALOG_TARGET, $sortOrderDialogTarget);
        }
    }
}
