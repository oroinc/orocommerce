<?php

namespace Oro\Bundle\CheckoutBundle\Form\Extension;

use Oro\Bundle\CheckoutBundle\Form\Type\CheckoutAddressSelectType;
use Oro\Bundle\CheckoutBundle\Layout\Provider\CheckoutThemeBCProvider;
use Oro\Bundle\CheckoutBundle\WorkflowState\Handler\CheckoutErrorHandler;
use Oro\Bundle\FormBundle\Utils\FormUtils;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowTransitionType;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * Workflow extension that is responsible for:
 *  - saving form errors without workflow state errors into form view,
 *  - forbidding mapping for workflow transition attributes if the address is manually.
 */
class CheckoutWorkflowStateExtension extends AbstractTypeExtension
{
    private const string CONTINUE_TO_SHIPPING_ADDRESS = 'continue_to_shipping_address';
    private const string CONTINUE_TO_SHIPPING_METHOD = 'continue_to_shipping_method';

    private const array TRANSITION_ATTRIBUTES = [
        self::CONTINUE_TO_SHIPPING_ADDRESS => ['email', 'save_billing_address'],
        self::CONTINUE_TO_SHIPPING_METHOD  => ['save_shipping_address']
    ];

    public function __construct(
        private CheckoutErrorHandler $checkoutErrorHandler,
        private CheckoutThemeBCProvider $checkoutThemeBCProvider,
    ) {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'onPreSubmit']);
    }

    public function onPreSubmit(FormEvent $event): void
    {
        if ($this->checkoutThemeBCProvider->isOldTheme()) {
            return;
        }

        $form = $event->getForm();
        if (!$form->getData() instanceof WorkflowData) {
            return;
        }

        $transition = $form->getConfig()->getOption('transition_name');
        if (!in_array($transition, \array_keys(self::TRANSITION_ATTRIBUTES), true)) {
            return;
        }

        $addressValue = $this->getCustomerAddressValue($event->getData(), $transition);
        if ($addressValue !== CheckoutAddressSelectType::ENTER_MANUALLY) {
            return;
        }

        $attributes = self::TRANSITION_ATTRIBUTES[$transition];
        foreach ($form->all() as $child) {
            if (\in_array($child->getName(), $attributes, true)) {
                FormUtils::replaceFieldOptionsRecursive($form, $child->getName(), ['mapped' => false]);
            }
        }
    }

    #[\Override]
    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        /** @var FormErrorIterator $errors */
        $errors = array_key_exists('errors', $view->vars) ? $view->vars['errors'] : new FormErrorIterator($form, []);

        $view->vars['errors'] = $this->checkoutErrorHandler->filterWorkflowStateError($errors);
    }

    #[\Override]
    public static function getExtendedTypes(): iterable
    {
        return [WorkflowTransitionType::class];
    }

    private function getCustomerAddressValue(array $data, string $transition): ?string
    {
        $addressField = $transition === self::CONTINUE_TO_SHIPPING_ADDRESS ? 'billing_address' : 'shipping_address';

        return $data[$addressField]['customerAddress'] ?? null;
    }
}
