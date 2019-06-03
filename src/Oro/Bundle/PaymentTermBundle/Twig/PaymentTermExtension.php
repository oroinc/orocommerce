<?php

namespace Oro\Bundle\PaymentTermBundle\Twig;

use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProviderInterface;

/**
 * Twig extension that provides payment term
 */
class PaymentTermExtension extends \Twig_Extension
{
    /** @var PaymentTermProviderInterface */
    protected $dataProvider;

    /**
     * @param PaymentTermProviderInterface $dataProvider
     */
    public function __construct(PaymentTermProviderInterface $dataProvider)
    {
        $this->dataProvider = $dataProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [new \Twig_SimpleFunction('get_payment_term', [$this, 'getPaymentTerm'])];
    }

    /**
     * @param object $object
     *
     * @return PaymentTerm|null
     */
    public function getPaymentTerm($object)
    {
        return $this->dataProvider->getObjectPaymentTerm($object);
    }
}
