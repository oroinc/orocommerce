<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Request;

use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @link https://developer.paypal.com/docs/classic/payflow/integration-guide/#core-credit-card-parameters
 */
abstract class AbstractRequest implements RequestInterface
{
    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var OptionsResolver
     */
    protected $resolver;

    public function __construct()
    {
        $this->resolver = new OptionsResolver();
    }

    /** {@inheritdoc} */
    public function setOptions(array $options = [])
    {
        $this->configureBaseOptions();
        $this->configureOptions();
        $this->configureFinalOptions();

        $this->options = $this->resolver->resolve($options);
    }

    /** {@inheritdoc} */
    public function getOptions()
    {
        return $this->options;
    }

    public function configureBaseOptions()
    {
        $this->resolver
            ->setDefault(Option\Action::TRANSACTION_TYPE, $this->getAction())
            ->addAllowedValues(Option\Action::TRANSACTION_TYPE, $this->getAction())
            ->setRequired([Option\User::USER, Option\User::PASSWORD, Option\User::VENDOR, Option\User::PARTNER])
            ->addAllowedValues(Option\User::PARTNER, Option\Partner::$list);
    }

    public function configureOptions()
    {
    }

    public function configureFinalOptions()
    {
    }

    /**
     * @param Option\OptionInterface $option
     * @return AbstractRequest
     */
    protected function addOption(Option\OptionInterface $option)
    {
        $option->configureOption($this->resolver);

        return $this;
    }
}
