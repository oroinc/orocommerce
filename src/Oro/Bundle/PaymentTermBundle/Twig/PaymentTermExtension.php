<?php

namespace Oro\Bundle\PaymentTermBundle\Twig;

use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProvider;

/**
 * Twig extension that provides payment term
 */
class PaymentTermExtension extends \Twig_Extension
{
    /** @var PaymentTermProvider */
    protected $dataProvider;

    /**
     * @param PaymentTermProvider $dataProvider
     */
    public function __construct(PaymentTermProvider $dataProvider)
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
