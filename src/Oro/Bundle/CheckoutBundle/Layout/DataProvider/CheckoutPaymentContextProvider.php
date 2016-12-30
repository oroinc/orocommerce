<?php

namespace Oro\Bundle\CheckoutBundle\Layout\DataProvider;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Factory\CheckoutPaymentContextFactory;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;

class CheckoutPaymentContextProvider
{
    /** @var CheckoutPaymentContextFactory */
    protected $paymentContextFactory;

    /**
     * @param CheckoutPaymentContextFactory $paymentContextFactory
     */
    public function __construct(CheckoutPaymentContextFactory $paymentContextFactory)
    {
        $this->paymentContextFactory = $paymentContextFactory;
    }

    /**
     * @param Checkout $entity
     * @return PaymentContextInterface
     */
    public function getContext(Checkout $entity)
    {
        return $this->paymentContextFactory->create($entity);
    }
}
