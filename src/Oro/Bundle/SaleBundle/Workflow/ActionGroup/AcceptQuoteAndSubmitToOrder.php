<?php

namespace Oro\Bundle\SaleBundle\Workflow\ActionGroup;

use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutLineItemsProvider;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\StartCheckoutInterface;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteAddress;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\SaleBundle\Quote\Demand\Subtotals\Calculator\QuoteDemandSubtotalsCalculatorInterface;
use Oro\Component\Action\Exception\InvalidArgumentException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
* Service to accept Quote and start Checkout workflow for Order submission.
 */
class AcceptQuoteAndSubmitToOrder implements AcceptQuoteAndSubmitToOrderInterface
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private QuoteDemandSubtotalsCalculatorInterface $quoteDemandSubtotalsCalculator,
        private StartCheckoutInterface $startCheckout,
        private CheckoutLineItemsProvider $checkoutLineItemsProvider,
        private ActionExecutor $actionExecutor
    ) {
    }

    public function execute(QuoteDemand $data): array
    {
        $quote = $data->getQuote();
        if (!$quote) {
            throw new InvalidArgumentException('Quote for QuoteDemand was not found.');
        }

        $editLink = $this->urlGenerator->generate('oro_sale_quote_frontend_choice', ['id' => $data->getId()]);
        $disallowShippingMethodEdit = $quote->getShippingCost() !== null;

        $this->quoteDemandSubtotalsCalculator->calculateSubtotals($data);
        $checkoutData = $this->fillCheckoutDataByQuote($quote);

        $disallowShippingAddressEdit = false;
        $quoteShippingAddress = $quote->getShippingAddress();
        if ($quoteShippingAddress) {
            $shippingAddress = $this->createOrderAddressByQuoteAddress($quoteShippingAddress);

            $checkoutData['shippingAddress'] = $shippingAddress;
            $disallowShippingAddressEdit = true;
        }

        $checkoutStartResult = $this->startCheckout->execute(
            sourceCriteria: ['quoteDemand' => $data],
            force: true,
            data: $checkoutData,
            settings: [
                'allow_manual_source_remove' => false,
                'auto_remove_source' => false,
                'edit_order_link' => $editLink,
                'disallow_shipping_address_edit' => $disallowShippingAddressEdit,
                'disallow_shipping_method_edit' => $disallowShippingMethodEdit
            ],
            showErrors: true,
            startTransition: $this->getStartTransitionName($quote),
        );
        $this->notifyAboutChangedSkus($checkoutStartResult['checkout'], $data);

        return $checkoutStartResult;
    }

    protected function fillCheckoutDataByQuote(Quote $quote): array
    {
        $checkoutData = [];

        if ($quote->getEstimatedShippingCost()) {
            $checkoutData['shippingCost'] = $quote->getEstimatedShippingCost();
        }
        if ($quote?->getShippingMethod()) {
            $checkoutData['shippingMethod'] = $quote->getShippingMethod();
        }
        if ($quote?->getShippingMethodType()) {
            $checkoutData['shippingMethodType'] = $quote->getShippingMethodType();
        }
        if ($quote?->getShipUntil()) {
            $checkoutData['shipUntil'] = $quote->getShipUntil();
        }
        if ($quote?->getPoNumber()) {
            $checkoutData['poNumber'] = $quote->getPoNumber();
        }

        return $checkoutData;
    }

    protected function getStartTransitionName(Quote $quote): string
    {
        if (!$quote->getCustomerUser() || $quote->getCustomerUser()->isGuest()) {
            return 'start_from_quote_as_guest';
        }

        return 'start_from_quote';
    }

    protected function createOrderAddressByQuoteAddress(QuoteAddress $quoteShippingAddress): OrderAddress
    {
        $orderAddress = new OrderAddress();
        $orderAddress->setLabel($quoteShippingAddress->getLabel());
        $orderAddress->setOrganization($quoteShippingAddress->getOrganization());
        $orderAddress->setStreet($quoteShippingAddress->getStreet());
        $orderAddress->setStreet2($quoteShippingAddress->getStreet2());
        $orderAddress->setCity($quoteShippingAddress->getCity());
        $orderAddress->setPostalCode($quoteShippingAddress->getPostalCode());
        $orderAddress->setCountry($quoteShippingAddress->getCountry());
        $orderAddress->setRegion($quoteShippingAddress->getRegion());
        $orderAddress->setRegionText($quoteShippingAddress->getRegionText());
        $orderAddress->setNamePrefix($quoteShippingAddress->getNamePrefix());
        $orderAddress->setFirstName($quoteShippingAddress->getFirstName());
        $orderAddress->setMiddleName($quoteShippingAddress->getMiddleName());
        $orderAddress->setLastName($quoteShippingAddress->getLastName());
        $orderAddress->setNameSuffix($quoteShippingAddress->getNameSuffix());
        $orderAddress->setPhone($quoteShippingAddress->getPhone());
        $orderAddress->setFromExternalSource(true);

        return $orderAddress;
    }

    protected function notifyAboutChangedSkus(Checkout $checkout, QuoteDemand $data): void
    {
        if (!$checkout->getLineItems() || $checkout->getLineItems()->isEmpty()) {
            return;
        }

        $changesSkus = $this->checkoutLineItemsProvider->getProductSkusWithDifferences(
            $checkout->getLineItems(),
            $data->getDemandProducts()
        );
        if (count($changesSkus) > 0) {
            $this->actionExecutor->executeAction(
                'flash_message',
                [
                    'message' => 'oro.checkout.frontend.checkout.some_changes_in_line_items',
                    'message_parameters' => [
                        'skus' => implode(', ', $changesSkus)
                    ],
                    'type' => 'warning'
                ]
            );
        }
    }
}
