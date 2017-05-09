<?php

namespace Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Request;

use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Option;

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
            ->addOption(new Option\ApiLoginId())
            ->addOption(new Option\TransactionKey());

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(Option\OptionsResolver $resolver)
    {
        $this
            ->withResolver($resolver)
            ->configureRequiredOptions()
            ->configureRequestOptions()
            ->configureTransactionOptions()
            ->endResolver();
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
    protected function configureTransactionOptions()
    {
        $this->resolver
            ->setDefault(Option\Transaction::TRANSACTION_TYPE, $this->getTransactionType())
            ->addAllowedValues(Option\Transaction::TRANSACTION_TYPE, $this->getTransactionType());

        return $this;
    }

    /**
     * @param Option\OptionInterface $option
     * @return $this
     * @throws \InvalidArgumentException
     */
    protected function addOption(Option\OptionInterface $option)
    {
        if (!$this->resolver) {
            throw new \InvalidArgumentException('Call AbstractRequest->withResolver($resolver) first');
        }

        $this->resolver->addOption($option);

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
}
