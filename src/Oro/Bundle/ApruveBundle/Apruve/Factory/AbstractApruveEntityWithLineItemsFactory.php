<?php

namespace Oro\Bundle\ApruveBundle\Apruve\Factory;

use Oro\Bundle\ApruveBundle\Apruve\Factory\LineItem\ApruveLineItemFromPaymentLineItemFactoryInterface;
use Oro\Bundle\ApruveBundle\Apruve\Helper\AmountNormalizerInterface;
use Oro\Bundle\ApruveBundle\Provider\ShippingAmountProviderInterface;
use Oro\Bundle\ApruveBundle\Provider\TaxAmountProviderInterface;
use Oro\Bundle\PaymentBundle\Context\LineItem\Collection\PaymentLineItemCollectionInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;

abstract class AbstractApruveEntityWithLineItemsFactory extends AbstractApruveEntityFactory
{
    /**
     * @var ShippingAmountProviderInterface
     */
    protected $shippingAmountProvider;

    /**
     * @var TaxAmountProviderInterface
     */
    protected $taxAmountProvider;

    /**
     * @var ApruveLineItemFromPaymentLineItemFactoryInterface
     */
    protected $apruveLineItemFromPaymentLineItemFactory;

    /**
     * @param AmountNormalizerInterface                         $amountNormalizer
     * @param ApruveLineItemFromPaymentLineItemFactoryInterface $apruveLineItemFromPaymentLineItemFactory
     * @param ShippingAmountProviderInterface                   $shippingAmountProvider
     * @param TaxAmountProviderInterface                        $taxAmountProvider
     */
    public function __construct(
        AmountNormalizerInterface $amountNormalizer,
        ApruveLineItemFromPaymentLineItemFactoryInterface $apruveLineItemFromPaymentLineItemFactory,
        ShippingAmountProviderInterface $shippingAmountProvider,
        TaxAmountProviderInterface $taxAmountProvider
    ) {
        parent::__construct($amountNormalizer);

        $this->apruveLineItemFromPaymentLineItemFactory = $apruveLineItemFromPaymentLineItemFactory;
        $this->shippingAmountProvider = $shippingAmountProvider;
        $this->taxAmountProvider = $taxAmountProvider;
    }

    /**
     * @param PaymentLineItemCollectionInterface $lineItems
     *
     * @return array
     */
    protected function getLineItems($lineItems)
    {
        $apruveLineItems = [];
        foreach ($lineItems as $lineItem) {
            $apruveLineItems[] = $this->apruveLineItemFromPaymentLineItemFactory
                ->createFromPaymentLineItem($lineItem)
                ->getData();
        }

        return $apruveLineItems;
    }

    /**
     * @param PaymentContextInterface $paymentContext
     *
     * @return int
     */
    protected function getShippingCents(PaymentContextInterface $paymentContext)
    {
        $amount = $this->shippingAmountProvider->getShippingAmount($paymentContext);

        return $this->normalizeAmount($amount);
    }

    /**
     * @param PaymentContextInterface $paymentContext
     *
     * @return int
     */
    protected function getTaxCents(PaymentContextInterface $paymentContext)
    {
        $amount = $this->taxAmountProvider->getTaxAmount($paymentContext);

        return $this->normalizeAmount($amount);
    }

    /**
     * Get total amount for "amount_cents" property.
     * Sums total price of line items, shipping costs and taxes.
     *
     * @param PaymentContextInterface $paymentContext
     *
     * @return int
     */
    protected function getAmountCents(PaymentContextInterface $paymentContext)
    {
        $amountCents = $this->normalizePrice($paymentContext->getSubtotal());
        $amountCents += $this->getShippingCents($paymentContext);
        $amountCents += $this->getTaxCents($paymentContext);

        return $amountCents;
    }
}
