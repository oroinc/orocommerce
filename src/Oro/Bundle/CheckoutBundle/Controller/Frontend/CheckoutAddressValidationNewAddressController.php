<?php

namespace Oro\Bundle\CheckoutBundle\Controller\Frontend;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\AddressValidationBundle\Controller\Frontend\AbstractAddressValidationController;
use Oro\Bundle\AddressValidationBundle\Form\Type\Frontend\FrontendAddressBookAwareAddressValidationResultType;
use Oro\Bundle\CheckoutBundle\AddressValidation\CheckoutHandler\AddressValidationCheckoutHandlerInterface;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\LayoutBundle\Attribute\Layout;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\SecurityBundle\Attribute\CsrfProtection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Renders the Address Validation dialog for the New Address dialog on checkout, handles its submit.
 */
class CheckoutAddressValidationNewAddressController extends AbstractAddressValidationController
{
    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            ...parent::getSubscribedServices(),
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
    #[ParamConverter('checkout', class: Checkout::class, options: ['id' => 'id'])]
    #[\Override]
    public function addressValidationAction(Request $request): Response|array
    {
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
                'allow_update_address' => false,
            ]
        );
    }

    #[\Override]
    protected function getWidgetEventSuccessResponse(
        FormInterface $addressForm,
        FormInterface $addressValidationResultForm,
        Request $request
    ): JsonResponse {
        $newAddressForm = $this->createAddressForm($request, $addressValidationResultForm->get('address')->getData());
        $newAddressFormRoot = $newAddressForm->getRoot();
        $selectedAddressIndex = (string)$addressValidationResultForm->get('address')->getViewData();

        /** @var OrderAddress $selectedAddress */
        $selectedAddress = $addressValidationResultForm->get('address')->getData();
        $this
            ->getAddressValidationCheckoutHandler()
            ->handle($request->attributes->get('checkout'), $selectedAddress);

        return $this->getWidgetEventResponse(true, 'success', [
            'selectedAddressIndex' => $selectedAddressIndex,
            'addressForm' => $this->renderView(
                '@OroAddressValidation/AddressValidation/addressForm.html.twig',
                ['form' => $newAddressFormRoot]
            ),
        ]);
    }

    private function getAddressValidationCheckoutHandler(): AddressValidationCheckoutHandlerInterface
    {
        return $this->container->get(AddressValidationCheckoutHandlerInterface::class);
    }
}
