<?php

namespace Oro\Bundle\CheckoutBundle\Provider;

use Oro\Bundle\CacheBundle\Provider\MemoryCacheProviderAwareTrait;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Factory\CheckoutPaymentContextFactory;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;

/**
 * Provides payment context for given checkout entity.
 */
class CheckoutPaymentContextProvider
{
    use MemoryCacheProviderAwareTrait;

    /** @var CheckoutPaymentContextFactory */
    private $paymentContextFactory;

    public function __construct(CheckoutPaymentContextFactory $paymentContextFactory)
    {
        $this->paymentContextFactory = $paymentContextFactory;
    }

    public function getContext(Checkout $entity): ?PaymentContextInterface
    {
        return $this->getMemoryCacheProvider()->get(
            ['checkout' => $entity],
            function () use ($entity) {
                return $this->paymentContextFactory->create($entity);
            }
        );
    }
}
