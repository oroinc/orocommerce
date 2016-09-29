<?php

namespace Oro\Bundle\InfinitePayBundle\Method\View;

use Oro\Bundle\InfinitePayBundle\Configuration\InfinitePayConfigInterface;
use Oro\Bundle\InfinitePayBundle\Form\Type\DebtorDataType;
use Oro\Bundle\InfinitePayBundle\Method\InfinitePay;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;
use Symfony\Component\Form\FormFactoryInterface;

class InfinitePayView implements PaymentMethodViewInterface
{
    /** @var FormFactoryInterface */
    protected $formFactory;

    /** @var InfinitePayConfigInterface */
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
     */
    public function getOptions(PaymentContextInterface $context)
    {
        $formView = $this->formFactory->create(DebtorDataType::NAME)->createView();

        return ['formView' => $formView];
    }

    /**
     * @return string
     */
    public function getBlock()
    {
        return '_payment_methods_infinite_pay_widget';
    }

    /**
     * @return int
     */
    public function getOrder()
    {
        return $this->config->getOrder();
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
    public function getPaymentMethodType()
    {
        return InfinitePay::TYPE;
    }
}
