<?php

namespace Oro\Bundle\CheckoutBundle\Factory;

use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Converter\OrderShippingLineItemConverterInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\ShippingBundle\Context\Builder\Factory\ShippingContextBuilderFactoryInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;

/**
 * Provides scope of data required to calculate correct shipping cost for checkout.
 */
class CheckoutShippingContextFactory
{
    /**
     * @var CheckoutLineItemsManager
     */
    protected $checkoutLineItemsManager;

    /**
     * @var TotalProcessorProvider
     */
    protected $totalProcessor;

    /**
     * @var OrderShippingLineItemConverterInterface
     */
    private $shippingLineItemConverter;

    /**
     * @var ShippingContextBuilderFactoryInterface|null
     */
    private $shippingContextBuilderFactory;

    /**
     * @param CheckoutLineItemsManager $checkoutLineItemsManager
     * @param TotalProcessorProvider $totalProcessor
     * @param OrderShippingLineItemConverterInterface $shippingLineItemConverter
     * @param null|ShippingContextBuilderFactoryInterface $shippingContextBuilderFactory
     */
    public function __construct(
        CheckoutLineItemsManager $checkoutLineItemsManager,
        TotalProcessorProvider $totalProcessor,
        OrderShippingLineItemConverterInterface $shippingLineItemConverter,
        ShippingContextBuilderFactoryInterface $shippingContextBuilderFactory = null
    ) {
        $this->checkoutLineItemsManager = $checkoutLineItemsManager;
        $this->totalProcessor = $totalProcessor;
        $this->shippingLineItemConverter = $shippingLineItemConverter;
        $this->shippingContextBuilderFactory = $shippingContextBuilderFactory;
    }

    /**
     * @param Checkout $checkout
     *
     * @return ShippingContextInterface|null
     */
    public function create(Checkout $checkout)
    {
        if (null === $this->shippingContextBuilderFactory) {
            return null;
        }

        $lineItems = $this->checkoutLineItemsManager->getData($checkout);
        $convertedLineItems = $this->shippingLineItemConverter->convertLineItems($lineItems);

        $shippingContextBuilder = $this->shippingContextBuilderFactory->createShippingContextBuilder(
            $checkout,
            (string)$checkout->getId()
        );

        $total = $this->getTotal($checkout);

        $shippingContextBuilder
            ->setSubTotal($total)
            ->setCurrency($checkout->getCurrency());

        if (null !== $checkout->getWebsite()) {
            $shippingContextBuilder
                ->setWebsite($checkout->getWebsite());
        }

        if (null !== $checkout->getShippingAddress()) {
            $shippingContextBuilder->setShippingAddress($checkout->getShippingAddress());
        }

        if (null !== $checkout->getBillingAddress()) {
            $shippingContextBuilder->setBillingAddress($checkout->getBillingAddress());
        }

        if (null !== $checkout->getPaymentMethod()) {
            $shippingContextBuilder->setPaymentMethod($checkout->getPaymentMethod());
        }

        if (null !== $convertedLineItems) {
            $shippingContextBuilder->setLineItems($convertedLineItems);
        }

        if (null !== $checkout->getCustomer()) {
            $shippingContextBuilder->setCustomer($checkout->getCustomer());
            $shippingContextBuilder->setCustomerUser($checkout->getCustomerUser());
        }

        return $shippingContextBuilder->getResult();
    }

    /**
     * Get checkout grand total and subtract shipping cost if it exists, because we need to calculate shipping cost
     * based on total that should not include shipping subtotal.
     *
     * @param Checkout $checkout
     * @return Price
     */
    private function getTotal(Checkout $checkout)
    {
        $total = $this->totalProcessor->getTotal($checkout);

        $shippingCost = $checkout->getShippingCost();
        $totalValue = $total->getAmount();

        if ($shippingCost && $total->getCurrency() === $shippingCost->getCurrency()) {
            $totalValue -= $shippingCost->getValue();
        }

        return Price::create($totalValue, $total->getCurrency());
    }
}
