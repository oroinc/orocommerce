<?php

namespace Oro\Bundle\ApruveBundle\Apruve\Builder;

use Oro\Bundle\ApruveBundle\Apruve\Builder\Factory\ApruveLineItemBuilderFactoryInterface;
use Oro\Bundle\ApruveBundle\Apruve\Request\Order\ApruveOrderRequestData;
use Oro\Bundle\ApruveBundle\Method\Config\ApruveConfigInterface;
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
     * @var array
     */
    private $data = [];

    /**
     * @param PaymentContextInterface $paymentContext
     * @param ApruveConfigInterface $config
     * @param ApruveLineItemBuilderFactoryInterface $lineItemBuilderFactory
     */
    public function __construct(
        PaymentContextInterface $paymentContext,
        ApruveConfigInterface $config,
        ApruveLineItemBuilderFactoryInterface $lineItemBuilderFactory
    ) {
        $this->paymentContext = $paymentContext;
        $this->config = $config;
        $this->lineItemBuilderFactory = $lineItemBuilderFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function getResult()
    {
        $this->data += [
            self::MERCHANT_ID => (string) $this->config->getMerchantId(),
            self::AMOUNT_CENTS => (int) $this->normalizePrice($this->paymentContext->getSubtotal()),
            self::CURRENCY => (string) $this->paymentContext->getCurrency(),
            self::LINE_ITEMS => $this->getLineItems($this->paymentContext->getLineItems()),
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
    public function setShippingAmount($amount)
    {
        $this->data[self::SHIPPING_CENTS] = $this->normalizeAmount($amount);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setTaxAmount($amount)
    {
        $this->data[self::TAX_CENTS] = $this->normalizeAmount($amount);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setShopperId($id)
    {
        $this->data[self::SHOPPER_ID] = (string) $id;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setMerchantOrderId($id)
    {
        $this->data[self::MERCHANT_ORDER_ID] = (string) $id;

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
}
