<?php

namespace Oro\Bundle\PaymentBundle\Layout\DataProvider;

use Oro\Bundle\CacheBundle\Generator\UniversalCacheKeyGenerator;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\ApplicablePaymentMethodsProvider;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewProviderInterface;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Returns payment methods
 */
class PaymentMethodViewsProvider
{
    private CacheInterface $cacheProvider;
    protected PaymentMethodViewProviderInterface $paymentMethodViewProvider;
    protected ApplicablePaymentMethodsProvider $paymentMethodProvider;
    protected PaymentTransactionProvider $paymentTransactionProvider;

    public function __construct(
        PaymentMethodViewProviderInterface $paymentMethodViewProvider,
        ApplicablePaymentMethodsProvider $paymentMethodProvider,
        PaymentTransactionProvider $transactionProvider,
        CacheInterface $cacheProvider
    ) {
        $this->paymentMethodViewProvider = $paymentMethodViewProvider;
        $this->paymentMethodProvider = $paymentMethodProvider;
        $this->paymentTransactionProvider = $transactionProvider;
        $this->cacheProvider = $cacheProvider;
    }

    public function getViews(PaymentContextInterface $context): array
    {
        $contextHash = UniversalCacheKeyGenerator::normalizeCacheKey(self::class . \md5(\serialize($context)));
        return $this->cacheProvider->get($contextHash, function () use ($context) {
            $paymentMethodViews = [];
            $methods = $this->paymentMethodProvider->getApplicablePaymentMethods($context);
            if (count($methods) !== 0) {
                $methodIdentifiers = array_map(function (PaymentMethodInterface $method) {
                    return $method->getIdentifier();
                }, $methods);

                $views = $this->paymentMethodViewProvider->getPaymentMethodViews($methodIdentifiers);
                foreach ($views as $view) {
                    $paymentMethodViews[$view->getPaymentMethodIdentifier()] = [
                        'label' => $view->getLabel(),
                        'block' => $view->getBlock(),
                        'options' => $view->getOptions($context),
                    ];
                }
            }
            return $paymentMethodViews;
        });
    }

    public function getPaymentMethods(object|string $entity): array
    {
        return $this->paymentTransactionProvider->getPaymentMethods($entity);
    }
}
