<?php

namespace Oro\Bundle\CheckoutBundle\Form\Extension;

use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowTransitionType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * Provides backward compatibility support for the `csrf` field in the checkout workflow form.
 * This is necessary to ensure that older frontend implementations relying on this field continue to function correctly,
 * even if the backend is updated separately.
 */
class CheckoutWorkflowCsrfBCExtension extends AbstractTypeExtension
{
    public function __construct(private string $csrfFieldName)
    {
    }

    #[\Override]
    public static function getExtendedTypes(): iterable
    {
        return [WorkflowTransitionType::class];
    }

    #[\Override]
    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        $field = $this->getCsrfFieldName($options);
        if (empty($options['csrf_protection']) && empty($view->children[$field])) {
            $factory = $form->getConfig()->getFormFactory();
            $csrfForm = $factory->createNamed($field, HiddenType::class, null, ['mapped' => false]);
            $view->children[$field] = $csrfForm->createView($view);
        }
    }

    private function getCsrfFieldName(array $options): ?string
    {
        return $options['csrf_field_name'] ?? $this->csrfFieldName;
    }
}
