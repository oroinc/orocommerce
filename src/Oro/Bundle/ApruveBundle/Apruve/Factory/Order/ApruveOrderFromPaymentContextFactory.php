<?php

namespace Oro\Bundle\ApruveBundle\Apruve\Factory\Order;

use Oro\Bundle\ApruveBundle\Apruve\Builder\Order\ApruveOrderBuilderFactoryInterface;
use Oro\Bundle\ApruveBundle\Apruve\Factory\AbstractApruveEntityWithLineItemsFactory;
use Oro\Bundle\ApruveBundle\Apruve\Factory\LineItem\ApruveLineItemFromPaymentLineItemFactoryInterface;
use Oro\Bundle\ApruveBundle\Apruve\Helper\AmountNormalizerInterface;
use Oro\Bundle\ApruveBundle\Method\Config\ApruveConfigInterface;
use Oro\Bundle\ApruveBundle\Provider\ShippingAmountProviderInterface;
use Oro\Bundle\ApruveBundle\Provider\TaxAmountProviderInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;

class ApruveOrderFromPaymentContextFactory extends AbstractApruveEntityWithLineItemsFactory implements
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
     * @param AmountNormalizerInterface                         $amountNormalizer
     * @param ApruveLineItemFromPaymentLineItemFactoryInterface $apruveLineItemFromPaymentLineItemFactory
     * @param ShippingAmountProviderInterface                   $shippingAmountProvider
     * @param TaxAmountProviderInterface                        $taxAmountProvider
     * @param ApruveOrderBuilderFactoryInterface                $apruveOrderBuilderFactory
     */
    public function __construct(
        AmountNormalizerInterface $amountNormalizer,
        ApruveLineItemFromPaymentLineItemFactoryInterface $apruveLineItemFromPaymentLineItemFactory,
        ShippingAmountProviderInterface $shippingAmountProvider,
        TaxAmountProviderInterface $taxAmountProvider,
        ApruveOrderBuilderFactoryInterface $apruveOrderBuilderFactory
    ) {
        parent::__construct(
            $amountNormalizer,
            $apruveLineItemFromPaymentLineItemFactory,
            $shippingAmountProvider,
            $taxAmountProvider
        );

        $this->apruveOrderBuilderFactory = $apruveOrderBuilderFactory;
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
     * @param PaymentContextInterface $paymentContext
     *
     * @return string
     */
    private function getMerchantOrderId(PaymentContextInterface $paymentContext)
    {
        return (string)$paymentContext->getSourceEntityIdentifier();
    }
}
