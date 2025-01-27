<?php

namespace Oro\Bundle\OrderBundle\Controller\AddressValidation;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\AddressValidationBundle\Controller\AbstractAddressValidationController;
use Oro\Bundle\AddressValidationBundle\Form\Type\AddressBookAwareAddressValidationResultType;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\RequestHandler\OrderRequestHandler;
use Oro\Bundle\SecurityBundle\Attribute\CsrfProtection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Renders the Address Validation dialog for shipping address, handles its submit for order create/edit page.
 */
class OrderAddressValidationController extends AbstractAddressValidationController
{
    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            ...parent::getSubscribedServices(),
            OrderRequestHandler::class,
        ];
    }

    #[CsrfProtection]
    #[ParamConverter('order', class: Order::class, options: ['id' => 'id'], isOptional: true)]
    #[Template('@OroAddressValidation/AddressValidation/addressBookAwareAddressValidationDialogWidget.html.twig')]
    #[\Override]
    public function addressValidationAction(Request $request): Response|array
    {
        $this->denyAccessUnlessGranted(
            $request->attributes->get('order') ? 'oro_order_update' : 'oro_order_create'
        );

        return parent::addressValidationAction($request);
    }

    #[\Override]
    protected function createAddressValidationResultForm(
        AbstractAddress $originalAddress,
        Request $request
    ): FormInterface {
        $suggestedAddresses = $this->getSuggestedAddresses($originalAddress);
        /** @var OrderRequestHandler $orderRequestHandler */
        $orderRequestHandler = $this->container->get(OrderRequestHandler::class);

        return $this->createForm(
            AddressBookAwareAddressValidationResultType::class,
            ['address' => $originalAddress],
            [
                'csrf_protection' => false,
                'original_address' => $originalAddress,
                'suggested_addresses' => $suggestedAddresses,
                'customer_user' => $orderRequestHandler->getCustomerUser(),
                'customer' => $orderRequestHandler->getCustomer(),
            ]
        );
    }

    #[\Override]
    protected function getWidgetEventSuccessResponse(
        FormInterface $addressValidationResultForm,
        Request $request
    ): JsonResponse {
        $addressForm = $this->createAddressForm($request, $addressValidationResultForm->get('address')->getData());
        $selectedAddressIndex = (string)$addressValidationResultForm->get('address')->getViewData();

        $createInAddressBook = false;
        if ($addressValidationResultForm->has('create_address')) {
            $createInAddressBook = (bool)$addressValidationResultForm->get('create_address')->getData();
        }

        $updateInAddressBook = false;
        if ($addressValidationResultForm->has('update_address')) {
            $updateInAddressBook = (bool)$addressValidationResultForm->get('update_address')->getData();
        }

        return $this->getWidgetEventResponse(true, 'success', [
            'selectedAddressIndex' => $selectedAddressIndex,
            'isAddressCreated' => $createInAddressBook,
            'isAddressUpdated' => $updateInAddressBook,
            'addressForm' => $this->renderView(
                '@OroAddressValidation/AddressValidation/addressForm.html.twig',
                ['form' => $addressForm]
            ),
        ]);
    }
}
