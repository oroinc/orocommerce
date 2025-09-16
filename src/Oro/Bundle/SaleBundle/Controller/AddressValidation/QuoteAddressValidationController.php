<?php

namespace Oro\Bundle\SaleBundle\Controller\AddressValidation;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\AddressValidationBundle\Controller\AbstractAddressValidationController;
use Oro\Bundle\AddressValidationBundle\Form\Type\AddressBookAwareAddressValidationResultType;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Model\QuoteRequestHandler;
use Oro\Bundle\SecurityBundle\Attribute\CsrfProtection;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Renders the Address Validation dialog for shipping address, handles its submit for quote create/edit page.
 */
class QuoteAddressValidationController extends AbstractAddressValidationController
{
    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            ...parent::getSubscribedServices(),
            QuoteRequestHandler::class,
        ];
    }

    #[CsrfProtection]
    #[Template('@OroAddressValidation/AddressValidation/addressBookAwareAddressValidationDialogWidget.html.twig')]
    #[\Override]
    public function addressValidationAction(
        Request $request,
        #[MapEntity(id: 'id')]
        Quote|null $quote = null
    ): Response|array {
        $this->denyAccessUnlessGranted(
            $request->attributes->get('quote') ? 'oro_sale_quote_update' : 'oro_sale_quote_create'
        );

        return parent::addressValidationAction($request);
    }

    #[\Override]
    protected function createAddressValidationResultForm(
        AbstractAddress $originalAddress,
        Request $request
    ): FormInterface {
        $suggestedAddresses = $this->getSuggestedAddresses($originalAddress);
        /** @var QuoteRequestHandler $quoteRequestHandler */
        $quoteRequestHandler = $this->container->get(QuoteRequestHandler::class);

        return $this->createForm(
            AddressBookAwareAddressValidationResultType::class,
            ['address' => $originalAddress],
            [
                'csrf_protection' => false,
                'original_address' => $originalAddress,
                'suggested_addresses' => $suggestedAddresses,
                'customer_user' => $quoteRequestHandler->getCustomerUser(),
                'customer' => $quoteRequestHandler->getCustomer(),
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
