<?php

namespace Oro\Bundle\CatalogBundle\EventListener;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\CatalogBundle\Utils\SortOrderDialogTargetStorage;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\UIBundle\Route\Router as UiRouter;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Seeks for "sortOrderDialogTarget" in input action parameter of a request and stores it in a session.
 */
class SortOrderDialogTargetFormHandlerEventListener
{
    public const SORT_ORDER_DIALOG_TARGET = 'sortOrderDialogTarget';

    private SortOrderDialogTargetStorage $sortOrderDialogTargetStorage;

    private UiRouter $uiRouter;

    private PropertyAccessorInterface $propertyAccessor;

    public function __construct(
        SortOrderDialogTargetStorage $sortOrderDialogTargetStorage,
        UiRouter $uiRouter,
        PropertyAccessorInterface $propertyAccessor
    ) {
        $this->sortOrderDialogTargetStorage = $sortOrderDialogTargetStorage;
        $this->uiRouter = $uiRouter;
        $this->propertyAccessor = $propertyAccessor;
    }

    public function onFormAfterFlush(AfterFormProcessEvent $event): void
    {
        try {
            $inputActionData = (array)$this->uiRouter->getInputActionData();
        } catch (\InvalidArgumentException $exception) {
            // UI router failed to parse input action data.
            $inputActionData = [];
        }

        $sortOrderDialogTarget = $inputActionData[self::SORT_ORDER_DIALOG_TARGET] ?? null;
        if (is_string($sortOrderDialogTarget)) {
            $form = $event->getForm();
            $targetEntity = $this->propertyAccessor
                ->getValue((object)[$form->getName() => $form], $sortOrderDialogTarget)
                ?->getData();
            if ($targetEntity !== null) {
                $this->sortOrderDialogTargetStorage->addTarget(
                    ClassUtils::getClass($targetEntity),
                    $targetEntity->getId()
                );
            }
        }
    }
}
