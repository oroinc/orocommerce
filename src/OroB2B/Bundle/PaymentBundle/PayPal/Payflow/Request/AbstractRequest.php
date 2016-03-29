<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Request;

use Symfony\Component\OptionsResolver\OptionsResolver;

use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;

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
     * Lazy initialized @see AbstractRequest::getOptionsResolver
     *
     * @var OptionsResolver
     */
    private $resolver;

    /**
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        if ($options) {
            $this->setOptions($options);
        }
    }

    /**
     * @return OptionsResolver
     */
    public function getOptionsResolver()
    {
        if (!$this->resolver) {
            $this->resolver = new OptionsResolver();
        }

        return $this->resolver;
    }

    /** {@inheritdoc} */
    public function setOptions(array $options = [])
    {
        $this->configureBaseOptions();
        $this->configureOptions();
        $this->configureFinalOptions();

        $this
            ->getOptionsResolver()
            ->setDefault(Option\Transaction::TRXTYPE, $this->getAction())
            ->addAllowedValues(Option\Transaction::TRXTYPE, $this->getAction());

        $this->options = $this->resolver->resolve($options);
    }

    /** {@inheritdoc} */
    public function getOptions()
    {
        return $this->options;
    }

    public function configureBaseOptions()
    {
        $this
            ->addOption(new Option\Transaction())
            ->addOption(new Option\Tender())
            ->addOption(new Option\User())
            ->addOption(new Option\Partner())
            ->addOption(new Option\Password())
            ->addOption(new Option\Vendor());
    }

    public function configureOptions()
    {
    }

    public function configureFinalOptions()
    {
    }

    /**
     * @param Option\OptionInterface $option
     * @return $this
     */
    protected function addOption(Option\OptionInterface $option)
    {
        $option->configureOption($this->getOptionsResolver());

        return $this;
    }
}
