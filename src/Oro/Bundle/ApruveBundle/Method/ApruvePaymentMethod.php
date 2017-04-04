<?php

namespace Oro\Bundle\ApruveBundle\Method;

use Oro\Bundle\ApruveBundle\Method\Config\ApruveConfigInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;

class ApruvePaymentMethod implements PaymentMethodInterface
{
    const TYPE = 'apruve';

    const COMPLETE = 'complete';
    const CANCEL = 'cancel';

    /**
     * @var ApruveConfigInterface
     */
    protected $config;

    /**
     * @param ApruveConfigInterface $config
     */
    public function __construct(ApruveConfigInterface $config)
    {
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function execute($action, PaymentTransaction $paymentTransaction)
    {
        // todo@webevt: implement payment actions processing once it becomes possible.
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        return $this->config->getPaymentMethodIdentifier();
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(PaymentContextInterface $context)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($actionName)
    {
        // todo@webevt: implement proper check for supported actions once it is possible.
        return true;
    }
}
