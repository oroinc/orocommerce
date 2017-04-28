<?php

namespace Oro\Bundle\ApruveBundle\Apruve\Factory\Order;

use Oro\Bundle\ApruveBundle\Apruve\Builder\Order\ApruveOrderBuilderFactoryInterface;
use Oro\Bundle\ApruveBundle\Apruve\Factory\AbstractApruveEntityFactory;
use Oro\Bundle\ApruveBundle\Apruve\Factory\LineItem\ApruveLineItemFromPaymentLineItemFactoryInterface;
use Oro\Bundle\ApruveBundle\Apruve\Helper\AmountNormalizerInterface;
use Oro\Bundle\ApruveBundle\Method\Config\ApruveConfigInterface;
use Oro\Bundle\ApruveBundle\Provider\ShippingAmountProviderInterface;
use Oro\Bundle\ApruveBundle\Provider\TaxAmountProviderInterface;
use Oro\Bundle\PaymentBundle\Context\LineItem\Collection\PaymentLineItemCollectionInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;

class ApruveOrderFromPaymentContextFactory extends AbstractApruveEntityFactory implements
    ApruveOrderFromPaymentContextFactoryInterface
{
    /**
     * @internal
     */
    const FINALIZE_ON_CREATE = true;

    /**
     * @internal
     */
    const INVOICE_ON_CREATE = false;

    /**
     * @var ApruveOrderBuilderFactoryInterface
     */
    private $apruveOrderBuilderFactory;

    /**
     * @var ShippingAmountProviderInterface
     */
    private $shippingAmountProvider;

    /**
     * @var TaxAmountProviderInterface
     */
    private $taxAmountProvider;

    /**
     * @var ApruveLineItemFromPaymentLineItemFactoryInterface
     */
    private $apruveLineItemFromPaymentLineItemFactory;

    /**
     * @param AmountNormalizerInterface                         $amountNormalizer
     * @param ApruveOrderBuilderFactoryInterface                $apruveOrderBuilderFactory
     * @param ApruveLineItemFromPaymentLineItemFactoryInterface $apruveLineItemFromPaymentLineItemFactory
     * @param ShippingAmountProviderInterface                   $shippingAmountProvider
     * @param TaxAmountProviderInterface                        $taxAmountProvider
     */
    public function __construct(
        AmountNormalizerInterface $amountNormalizer,
        ApruveOrderBuilderFactoryInterface $apruveOrderBuilderFactory,
        ApruveLineItemFromPaymentLineItemFactoryInterface $apruveLineItemFromPaymentLineItemFactory,
        ShippingAmountProviderInterface $shippingAmountProvider,
        TaxAmountProviderInterface $taxAmountProvider
    ) {
        parent::__construct($amountNormalizer);

        $this->apruveOrderBuilderFactory = $apruveOrderBuilderFactory;
        $this->apruveLineItemFromPaymentLineItemFactory = $apruveLineItemFromPaymentLineItemFactory;
        $this->shippingAmountProvider = $shippingAmountProvider;
        $this->taxAmountProvider = $taxAmountProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function createFromPaymentContext(
        PaymentContextInterface $paymentContext,
        ApruveConfigInterface $apruveConfig
    ) {
        $apruveOrderBuilder = $this->apruveOrderBuilderFactory
            ->create(
                $apruveConfig->getMerchantId(),
                $this->getAmountCents($paymentContext),
                $paymentContext->getCurrency(),
                $this->getLineItems($paymentContext->getLineItems())
            );

        $apruveOrderBuilder
            ->setMerchantOrderId($this->getMerchantOrderId($paymentContext))
            ->setShippingCents($this->getShippingCents($paymentContext))
            ->setTaxCents($this->getTaxCents($paymentContext));

        $apruveOrderBuilder
            ->setFinalizeOnCreate(self::FINALIZE_ON_CREATE)
            ->setInvoiceOnCreate(self::INVOICE_ON_CREATE);

        return $apruveOrderBuilder->getResult();
    }

    /**
     * @param PaymentLineItemCollectionInterface $lineItems
     *
     * @return array
     */
    private function getLineItems($lineItems)
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
    private function getShippingCents(PaymentContextInterface $paymentContext)
    {
        $amount = $this->shippingAmountProvider->getShippingAmount($paymentContext);

        return $this->normalizeAmount($amount);
    }

    /**
     * @param PaymentContextInterface $paymentContext
     *
     * @return int
     */
    private function getTaxCents(PaymentContextInterface $paymentContext)
    {
        $amount = $this->taxAmountProvider->getTaxAmount($paymentContext);

        return $this->normalizeAmount($amount);
    }

    /**
     * @param PaymentContextInterface $paymentContext
     *
     * @return string
     */
    private function getMerchantOrderId(PaymentContextInterface $paymentContext)
    {
        return (string)$paymentContext->getSourceEntityIdentifier();
    }

    /**
     * Get total order amount for "amount_cents" property.
     * Sums total price of line items, shipping costs and taxes.
     *
     * @param PaymentContextInterface $paymentContext
     *
     * @return int
     */
    private function getAmountCents(PaymentContextInterface $paymentContext)
    {
        $amountCents = $this->normalizePrice($paymentContext->getSubtotal());
        $amountCents += $this->getShippingCents($paymentContext);
        $amountCents += $this->getTaxCents($paymentContext);

        return $amountCents;
    }
}
