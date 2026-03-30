<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Form\Extension;

use Oro\Bundle\OrderBundle\DraftSession\Manager\OrderDraftManager;
use Oro\Bundle\OrderBundle\Form\Type\OrderType;
use Oro\Bundle\OrderBundle\Form\Type\SubOrderType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\ClearableErrorsInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Removes lineItems field from OrderType when draft edit mode is enabled.
 */
class RemoveLineItemsFromOrderTypeExtension extends AbstractTypeExtension
{
    public function __construct(
        private readonly OrderDraftManager $orderDraftManager,
        private readonly TranslatorInterface $translator
    ) {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (!$this->orderDraftManager->getDraftSessionUuid()) {
            return;
        }

        $builder->remove('lineItems');

        // Must be executed after {@see \Symfony\Component\Form\Extension\Validator\EventListener\ValidationListener}.
        $builder->addEventListener(FormEvents::POST_SUBMIT, $this->onPostSubmit(...), -100);
    }

    /**
     * Replaces line items validation errors with a general error message.
     */
    private function onPostSubmit(FormEvent $event): void
    {
        $form = $event->getForm();
        if (!$form instanceof ClearableErrorsInterface) {
            return;
        }

        if ($form->isValid()) {
            return;
        }

        $hasLineItemsError = false;
        $formErrors = [];

        /** @var FormError[] $fieldErrors */
        $fieldErrors = $form->getErrors();
        foreach ($fieldErrors as $formError) {
            $constraintViolation = $formError->getCause();
            if (
                $constraintViolation instanceof ConstraintViolationInterface &&
                str_starts_with($constraintViolation->getPropertyPath(), 'data.lineItems[')
            ) {
                $hasLineItemsError = true;
            } else {
                $formErrors[] = $formError;
            }
        }

        if ($hasLineItemsError) {
            $form->clearErrors();

            $form->addError(
                new FormError(
                    $this->translator->trans('oro.order.line_items_general_error.message', [], 'validators'),
                    'oro.order.line_items_general_error.message',
                    []
                )
            );

            foreach ($formErrors as $formError) {
                $form->addError($formError);
            }
        }
    }

    #[\Override]
    public static function getExtendedTypes(): iterable
    {
        return [OrderType::class, SubOrderType::class];
    }
}
