<?php

namespace Oro\Bundle\CheckoutBundle\Controller\Frontend;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\AddressValidationBundle\Controller\Frontend\AbstractAddressValidationController;
use Oro\Bundle\AddressValidationBundle\Form\Type\Frontend\FrontendAddressBookAwareAddressValidationResultType;
use Oro\Bundle\CheckoutBundle\AddressValidation\CheckoutHandler\AddressValidationCheckoutHandlerInterface;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\AddressValidation\CheckoutAddressFormAddressProviderInterface;
use Oro\Bundle\LayoutBundle\Attribute\Layout;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\SecurityBundle\Attribute\CsrfProtection;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Renders the Address Validation dialog on checkout, handles its submit.
 */
class CheckoutAddressValidationController extends AbstractAddressValidationController
{
    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            ...parent::getSubscribedServices(),
            CheckoutAddressFormAddressProviderInterface::class,
            AddressValidationCheckoutHandlerInterface::class,
        ];
    }

    #[Acl(
        id: 'oro_checkout_frontend_checkout',
        type: 'entity',
        class: Checkout::class,
        permission: 'EDIT',
        groupName: 'commerce'
    )]
    #[CsrfProtection]
    #[Layout]
    #[\Override]
    public function addressValidationAction(
        Request $request,
        #[MapEntity(id: 'id')]
        Checkout|null $checkout = null
    ): Response|array {
        return parent::addressValidationAction($request);
    }

    #[\Override]
    protected function handleAddressFormRequest(FormInterface $addressForm, Request $request): void
    {
        $submittedData = $request->request->all()[$addressForm->getRoot()->getName()] ?? [];

        // $clearMissing must be true to ensure that CheckoutAddressType works properly
        // when CheckoutAddressSelectType is not displayed.
        $addressForm->getRoot()->submit($submittedData, true);
    }

    #[\Override]
    protected function createAddressValidationResultForm(AbstractAddress $originalAddress): FormInterface
    {
        return $this->createForm(
            FrontendAddressBookAwareAddressValidationResultType::class,
            ['address' => $originalAddress],
            [
                'csrf_protection' => false,
                'original_address' => $originalAddress,
                'suggested_addresses' => $this->getSuggestedAddresses($originalAddress),
            ]
        );
    }

    #[\Override]
    protected function getOriginalAddress(FormInterface $addressForm, Request $request): AbstractAddress
    {
        return $this->container->get(CheckoutAddressFormAddressProviderInterface::class)->getAddress($addressForm);
    }

    #[\Override]
    protected function getWidgetEventSuccessResponse(
        FormInterface $addressForm,
        FormInterface $addressValidationResultForm,
        Request $request
    ): JsonResponse {
        /** @var OrderAddress $selectedAddress */
        $selectedAddress = $addressValidationResultForm->get('address')->getData();
        $this
            ->getAddressValidationCheckoutHandler()
            ->handle($request->attributes->get('checkout'), $selectedAddress, $addressForm->getRoot()->getData());

        return $this->getWidgetEventResponse(true, 'success', [
            'selectedAddressIndex' => $addressValidationResultForm->get('address')->getViewData(),
        ]);
    }

    private function getAddressValidationCheckoutHandler(): AddressValidationCheckoutHandlerInterface
    {
        return $this->container->get(AddressValidationCheckoutHandlerInterface::class);
    }
}
