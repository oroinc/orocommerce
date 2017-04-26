<?php

namespace Oro\Bundle\InfinitePayBundle\Method\View;

use Oro\Bundle\InfinitePayBundle\Form\Type\DebtorDataType;
use Oro\Bundle\InfinitePayBundle\Method\Config\InfinitePayConfigInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

class InfinitePayView implements PaymentMethodViewInterface
{
    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @var InfinitePayConfigInterface
     */
    protected $config;

    public function __construct(
        InfinitePayConfigInterface $config,
        FormFactoryInterface $formFactory
    ) {
        $this->config = $config;
        $this->formFactory = $formFactory;
    }

    /**
     * @param PaymentContextInterface $context
     * @return array
     * @throws InvalidOptionsException
     */
    public function getOptions(PaymentContextInterface $context)
    {
        $formView = $this->formFactory->create(DebtorDataType::class)->createView();

        return [
            'formView' => $formView,
            'paymentMethod' => $this->config->getPaymentMethodIdentifier(),
        ];
    }

    /**
     * @return string
     */
    public function getBlock()
    {
        return '_payment_methods_infinite_pay_widget';
    }

    /**
     * {@inheritdoc}
     */
    public function getAdminLabel()
    {
        return $this->config->getAdminLabel();
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->config->getLabel();
    }

    /**
     * {@inheritdoc}
     */
    public function getShortLabel()
    {
        return $this->config->getShortLabel();
    }

    /**
     * @return string
     */
    public function getPaymentMethodIdentifier()
    {
        return $this->config->getPaymentMethodIdentifier();
    }
}
