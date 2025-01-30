<?php

declare(strict_types=1);

namespace Oro\Bundle\CheckoutBundle\Form\Factory\AddressValidation;

use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Model\OperationRegistry;
use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\AddressValidationBundle\Form\Factory\AddressValidationAddressFormFactoryInterface;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Creates an address form used for the address validation of the new address on checkout.
 */
class CheckoutNewAddressFormFactory implements AddressValidationAddressFormFactoryInterface
{
    public function __construct(
        private readonly FormFactory $formFactory,
        private readonly OperationRegistry $operationRegistry,
        private readonly ContextHelper $contextHelper,
        private string $operationName
    ) {
    }

    #[\Override]
    public function createAddressForm(Request $request, ?AbstractAddress $address = null): FormInterface
    {
        $checkout = new Checkout();

        $operation = $this->operationRegistry->findByName($this->operationName);
        if (!$operation) {
            throw new \InvalidArgumentException('Operation ' . $this->operationName . ' is not found');
        }

        $actionData = $this->contextHelper->getActionData([
            ContextHelper::ROUTE_PARAM => 'oro_frontend_action_widget_form',
            ContextHelper::ROUTE_PARAMETERS => urlencode(
                json_encode(['operationName' => $operation->getName()], JSON_THROW_ON_ERROR)
            ),
            ContextHelper::ENTITY_ID_PARAM => $checkout->getId(),
            ContextHelper::ENTITY_CLASS_PARAM => Checkout::class,
        ]);

        $form = $this->formFactory->create(
            $operation->getDefinition()->getFormType(),
            $actionData,
            array_merge($operation->getFormOptions($actionData), ['operation' => $operation])
        );

        $addressForm = $form->get('address');
        if ($address !== null) {
            $addressForm->setData($address);
        }

        return $addressForm;
    }
}
