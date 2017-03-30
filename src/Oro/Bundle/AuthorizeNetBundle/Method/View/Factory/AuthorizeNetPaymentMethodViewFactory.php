<?php

namespace Oro\Bundle\AuthorizeNetBundle\Method\View\Factory;

use Oro\Bundle\AuthorizeNetBundle\Method\Config\AuthorizeNetConfigInterface;
use Oro\Bundle\AuthorizeNetBundle\Method\View\AuthorizeNetPaymentMethodView;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use Symfony\Component\Form\FormFactoryInterface;

class AuthorizeNetPaymentMethodViewFactory implements AuthorizeNetPaymentMethodViewFactoryInterface
{
    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    /**
     * @var PaymentTransactionProvider
     */
    private $transactionProvider;

    /**
     * @param FormFactoryInterface $formFactory
     * @param PaymentTransactionProvider $transactionProvider
     */
    public function __construct(FormFactoryInterface $formFactory, PaymentTransactionProvider $transactionProvider)
    {
        $this->formFactory = $formFactory;
        $this->transactionProvider = $transactionProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function create(AuthorizeNetConfigInterface $config)
    {
        return new AuthorizeNetPaymentMethodView($this->formFactory, $config, $this->transactionProvider);
    }
}
