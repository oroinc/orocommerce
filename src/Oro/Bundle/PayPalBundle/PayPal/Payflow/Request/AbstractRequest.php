<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Request;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;

/**
 * @link https://developer.paypal.com/docs/classic/payflow/integration-guide/#core-credit-card-parameters
 */
abstract class AbstractRequest implements RequestInterface
{
    /**
     * @var Option\OptionsResolver
     */
    protected $resolver;

    /**
     * @param Option\OptionsResolver $resolver
     * @return $this
     */
    protected function withResolver(Option\OptionsResolver $resolver)
    {
        $this->resolver = $resolver;

        return $this;
    }

    /**
     * @return $this
     */
    private function configureRequiredOptions()
    {
        $this
            ->addOption(new Option\Transaction())
            ->addOption(new Option\User())
            ->addOption(new Option\Partner())
            ->addOption(new Option\Password())
            ->addOption(new Option\Vendor());

        return $this;
    }

    /** {@inheritdoc} */
    public function configureOptions(Option\OptionsResolver $resolver)
    {
        $this
            ->withResolver($resolver)
            ->configureRequiredOptions()
            ->configureBaseOptions()
            ->configureRequestOptions()
            ->configureFinalOptions()
            ->configureTransactionOptions()
            ->endResolver();
    }

    /**
     * @return $this
     */
    protected function configureBaseOptions()
    {
        return $this;
    }

    /**
     * @return $this
     */
    protected function configureRequestOptions()
    {
        return $this;
    }

    /**
     * @return $this
     */
    protected function configureFinalOptions()
    {
        return $this;
    }

    /**
     * @return $this
     */
    protected function configureTransactionOptions()
    {
        $this->resolver
            ->setDefault(Option\Transaction::TRXTYPE, $this->getTransactionType())
            ->addAllowedValues(Option\Transaction::TRXTYPE, $this->getTransactionType());

        return $this;
    }

    /**
     * @return $this
     */
    private function endResolver()
    {
        $this->resolver = null;

        return $this;
    }

    /**
     * @param Option\OptionInterface $option
     * @return $this
     */
    protected function addOption(Option\OptionInterface $option)
    {
        if (!$this->resolver) {
            throw new \InvalidArgumentException('Call AbstractRequest->withResolver($resolver) first');
        }

        $this->resolver->addOption($option);

        return $this;
    }
}
