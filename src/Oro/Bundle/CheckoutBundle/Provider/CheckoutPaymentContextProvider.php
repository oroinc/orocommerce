<?php

namespace Oro\Bundle\CheckoutBundle\Provider;

use Oro\Bundle\CacheBundle\Provider\MemoryCacheProviderInterface;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Factory\CheckoutPaymentContextFactory;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;

/**
 * Provides payment context for a specific checkout entity.
 */
class CheckoutPaymentContextProvider
{
    private CheckoutPaymentContextFactory $paymentContextFactory;
    private MemoryCacheProviderInterface $memoryCacheProvider;

    public function __construct(
        CheckoutPaymentContextFactory $paymentContextFactory,
        MemoryCacheProviderInterface $memoryCacheProvider
    ) {
        $this->paymentContextFactory = $paymentContextFactory;
        $this->memoryCacheProvider = $memoryCacheProvider;
    }

    public function getContext(Checkout $entity): ?PaymentContextInterface
    {
        return $this->memoryCacheProvider->get(
            ['checkout' => $entity],
            function () use ($entity) {
                return $this->paymentContextFactory->create($entity);
            }
        );
    }
}
