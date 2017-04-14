<?php

namespace Oro\Bundle\ApruveBundle\Apruve\Builder;

use Oro\Bundle\ApruveBundle\Apruve\Builder\Factory\ApruveLineItemBuilderFactoryInterface;
use Oro\Bundle\ApruveBundle\Apruve\Request\Order\ApruveOrderRequestData;
use Oro\Bundle\ApruveBundle\Method\Config\ApruveConfigInterface;
use Oro\Bundle\ApruveBundle\Provider\ShippingAmountProviderInterface;
use Oro\Bundle\ApruveBundle\Provider\TaxAmountProviderInterface;
use Oro\Bundle\PaymentBundle\Context\LineItem\Collection\PaymentLineItemCollectionInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;

class ApruveOrderBuilder extends AbstractApruveEntityBuilder implements ApruveOrderBuilderInterface
{
    /**
     * Mandatory
     */
    const MERCHANT_ID = 'merchant_id';
    const AMOUNT_CENTS = 'amount_cents';
    const CURRENCY = 'currency';
    const LINE_ITEMS = 'order_items';

    /**
     * Optional
     */
    const MERCHANT_ORDER_ID = 'merchant_order_id';
    const TAX_CENTS = 'tax_cents';
    const SHIPPING_CENTS = 'shipping_cents';
    const EXPIRE_AT = 'expire_at';
    const AUTO_ESCALATE = 'auto_escalate';
    const PO_NUMBER = 'po_number';
    const PAYMENT_TERM_PARAMS = 'payment_term_params';
    const _CORPORATE_ACCOUNT_ID = 'corporate_account_id';
    const FINALIZE_ON_CREATE = 'finalize_on_create';
    const INVOICE_ON_CREATE = 'invoice_on_create';

    /**
     * Required for offline (created manually via Apruve API) orders only.
     */
    const SHOPPER_ID = 'shopper_id';

    /**
     * @var PaymentContextInterface
     */
    private $paymentContext;

    /**
     * @var ApruveConfigInterface
     */
    private $config;

    /**
     * @var ApruveLineItemBuilderFactoryInterface
     */
    private $lineItemBuilderFactory;

    /**
     * @var TaxAmountProviderInterface
     */
    private $taxAmountProvider;

    /**
     * @var ShippingAmountProviderInterface
     */
    private $shippingAmountProvider;

    /**
     * @var array
     */
    private $data = [];

    /**
     * @param PaymentContextInterface $paymentContext
     * @param ApruveConfigInterface $config
     * @param ApruveLineItemBuilderFactoryInterface $lineItemBuilderFactory
     * @param ShippingAmountProviderInterface $shippingAmountProvider
     * @param TaxAmountProviderInterface $taxAmountProvider
     */
    public function __construct(
        PaymentContextInterface $paymentContext,
        ApruveConfigInterface $config,
        ApruveLineItemBuilderFactoryInterface $lineItemBuilderFactory,
        ShippingAmountProviderInterface $shippingAmountProvider,
        TaxAmountProviderInterface $taxAmountProvider
    ) {
        $this->paymentContext = $paymentContext;
        $this->config = $config;
        $this->lineItemBuilderFactory = $lineItemBuilderFactory;
        $this->shippingAmountProvider = $shippingAmountProvider;
        $this->taxAmountProvider = $taxAmountProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function getResult()
    {
        $this->data += [
            self::MERCHANT_ID => (string) $this->config->getMerchantId(),
            self::MERCHANT_ORDER_ID => (string) $this->getMerchantOrderId($this->paymentContext),
            self::AMOUNT_CENTS => (int) $this->getAmountCents($this->paymentContext),
            self::CURRENCY => (string) $this->paymentContext->getCurrency(),
            self::LINE_ITEMS => (array) $this->getLineItems($this->paymentContext->getLineItems()),
            self::SHIPPING_CENTS => (int) $this->getShippingCents($this->paymentContext),
            self::TAX_CENTS => (int) $this->getTaxCents($this->paymentContext),
        ];

        return new ApruveOrderRequestData($this->data);
    }

    /**
     * {@inheritDoc}
     */
    public function setInvoiceOnCreate($bool)
    {
        $this->data[self::INVOICE_ON_CREATE] = (bool) $bool;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setFinalizeOnCreate($bool)
    {
        $this->data[self::FINALIZE_ON_CREATE] = (bool) $bool;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setShopperId($id)
    {
        $this->data[self::SHOPPER_ID] = (string)$id;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setCorporateAccountId($id)
    {
        $this->data[self::PAYMENT_TERM_PARAMS][self::_CORPORATE_ACCOUNT_ID] = (string)$id;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setExpireAt(\DateTime $dateTime)
    {
        $this->data[self::EXPIRE_AT] = (string)$dateTime->format(\DateTime::ATOM);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setAutoEscalate($bool)
    {
        $this->data[self::AUTO_ESCALATE] = (bool)$bool;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setPoNumber($poNumber)
    {
        $this->data[self::PO_NUMBER] = (string)$poNumber;

        return $this;
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
            $builder = $this->lineItemBuilderFactory->create($lineItem);

            $apruveLineItems[] = $builder->getResult()->getData();
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
     * @param PaymentContextInterface $paymentContext
     *
     * @return string
     */
    protected function getMerchantOrderId(PaymentContextInterface $paymentContext)
    {
        return (string) $paymentContext->getSourceEntityIdentifier();
    }

    /**
     * Get total order amount for "amount_cents" property.
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
