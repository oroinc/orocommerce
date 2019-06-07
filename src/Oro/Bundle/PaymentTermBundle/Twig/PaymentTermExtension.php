<?php

namespace Oro\Bundle\PaymentTermBundle\Twig;

use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProviderInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides a Twig function to get payment term from an entity:
 *   - get_payment_term
 */
class PaymentTermExtension extends AbstractExtension
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
        return [new TwigFunction('get_payment_term', [$this, 'getPaymentTerm'])];
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
