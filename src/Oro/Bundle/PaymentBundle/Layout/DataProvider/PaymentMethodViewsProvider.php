<?php

namespace Oro\Bundle\PaymentBundle\Layout\DataProvider;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\ApplicablePaymentMethodsProvider;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewProviderInterface;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;

/**
 * Returns payment methods
 */
class PaymentMethodViewsProvider
{
    /**
     * @var CacheProvider
     */
    private $cacheProvider;

    /**
     * @var PaymentMethodViewProviderInterface
     */
    protected $paymentMethodViewProvider;

    /**
     * @var ApplicablePaymentMethodsProvider
     */
    protected $paymentMethodProvider;

    /**
     * @var PaymentTransactionProvider
     */
    protected $paymentTransactionProvider;

    public function __construct(
        PaymentMethodViewProviderInterface $paymentMethodViewProvider,
        ApplicablePaymentMethodsProvider $paymentMethodProvider,
        PaymentTransactionProvider $transactionProvider,
        CacheProvider $cacheProvider
    ) {
        $this->paymentMethodViewProvider = $paymentMethodViewProvider;
        $this->paymentMethodProvider = $paymentMethodProvider;
        $this->paymentTransactionProvider = $transactionProvider;
        $this->cacheProvider = $cacheProvider;
    }

    /**
     * @param PaymentContextInterface $context
     *
     * @return array[]
     */
    public function getViews(PaymentContextInterface $context): array
    {
        $contextHash = self::class . \md5(\serialize($context));
        $cachedPaymentMethodViews = $this->cacheProvider->fetch($contextHash);

        if ($cachedPaymentMethodViews !== false) {
            return $cachedPaymentMethodViews;
        }

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

        $this->cacheProvider->save($contextHash, $paymentMethodViews);

        return $paymentMethodViews;
    }

    /**
     * @param object $entity
     *
     * @return array
     */
    public function getPaymentMethods($entity)
    {
        return $this->paymentTransactionProvider->getPaymentMethods($entity);
    }
}
