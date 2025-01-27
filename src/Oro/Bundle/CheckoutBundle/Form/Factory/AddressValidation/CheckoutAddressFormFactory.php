<?php

declare(strict_types=1);

namespace Oro\Bundle\CheckoutBundle\Form\Factory\AddressValidation;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\AddressValidationBundle\Form\Factory\AddressValidationAddressFormFactoryInterface;
use Oro\Bundle\CheckoutBundle\Helper\CheckoutWorkflowHelper;
use Oro\Bundle\CheckoutBundle\Layout\DataProvider\TransitionProvider;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Creates an address form used for the address validation on checkout.
 */
class CheckoutAddressFormFactory implements AddressValidationAddressFormFactoryInterface
{
    public function __construct(
        private CheckoutWorkflowHelper $checkoutWorkflowHelper,
        private TransitionProvider $transitionProvider,
        private FormFactoryInterface $formFactory,
        private string $addressFieldName
    ) {
    }

    #[\Override]
    public function createAddressForm(Request $request, AbstractAddress $address = null): FormInterface
    {
        $checkout = $request->attributes->get('checkout');
        $workflowItem = $this->checkoutWorkflowHelper->getWorkflowItem($checkout);

        $addressForm = $this
            ->createForm($workflowItem, (string)$request->get('transition'))
            ->get($this->addressFieldName);

        if ($address !== null) {
            $addressForm->setData($address);
        }

        return $addressForm;
    }

    private function createForm(WorkflowItem $workflowItem, string $transitionName): FormInterface
    {
        $transitionData = $this->transitionProvider->getContinueTransition($workflowItem, $transitionName);
        if (!$transitionData) {
            throw new \LogicException(
                sprintf('Continue transition data is not found for %s #%d', WorkflowItem::class, $workflowItem->getId())
            );
        }

        $transition = $transitionData->getTransition();
        if (!$transition->hasForm()) {
            throw new \LogicException('Transition does not have a form that can be used as address form');
        }

        return $this->formFactory->create(
            $transition->getFormType(),
            // Clones workflow data to avoid the accidental changes during form submit.
            clone $workflowItem->getData(),
            [
                ...$transition->getFormOptions(),
                'workflow_item' => $workflowItem,
                'transition_name' => $transition->getName(),
                'allow_extra_fields' => true,
            ]
        );
    }
}
