<?php

declare(strict_types=1);

namespace Oro\Bundle\SaleBundle\Form\Factory\AddressValidation;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\AddressValidationBundle\Form\Factory\AddressValidationAddressFormFactoryInterface;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Form\Type\QuoteType;
use Oro\Bundle\SaleBundle\Model\QuoteRequestHandler;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Creates an address form used for the address validation on the quote create/edit page.
 */
class QuotePageShippingAddressFormFactory implements AddressValidationAddressFormFactoryInterface
{
    public function __construct(
        private FormFactoryInterface $formFactory,
        private QuoteRequestHandler $quoteRequestHandler,
    ) {
    }

    #[\Override]
    public function createAddressForm(Request $request, AbstractAddress $address = null): FormInterface
    {
        $quote = (new Quote())
            ->setCustomer($this->quoteRequestHandler->getCustomer())
            ->setCustomerUser($this->quoteRequestHandler->getCustomerUser())
            ->setShippingAddress($address);

        return $this->formFactory
            ->create(QuoteType::class, $quote)
            ->get('shippingAddress');
    }
}
