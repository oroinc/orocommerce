<?php

namespace Oro\Bundle\CheckoutBundle\Layout\DataProvider;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Factory\CheckoutPaymentContextFactory;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;

/**
 * Provides payment context by checkout
 */
class CheckoutPaymentContextProvider
{
    /** @var CheckoutPaymentContextFactory */
    protected $paymentContextFactory;

    /** @var CacheProvider */
    private $cacheProvider;

    /**
     * @param CheckoutPaymentContextFactory $paymentContextFactory
     * @param CacheProvider $cacheProvider
     */
    public function __construct(CheckoutPaymentContextFactory $paymentContextFactory, CacheProvider $cacheProvider)
    {
        $this->paymentContextFactory = $paymentContextFactory;
        $this->cacheProvider = $cacheProvider;
    }

    /**
     * @param Checkout $entity
     * @return PaymentContextInterface
     */
    public function getContext(Checkout $entity)
    {
        $contextHash = self::class . \md5(\serialize($entity));
        $cachedContext = $this->cacheProvider->fetch($contextHash);
        if (!$cachedContext) {
            $cachedContext = $this->paymentContextFactory->create($entity);
            $this->cacheProvider->save($contextHash, $cachedContext);
        }

        return $cachedContext;
    }
}
