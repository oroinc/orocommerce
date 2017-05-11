<?php

namespace Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Request;

use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Option;

class AuthorizeRequest extends AbstractRequest
{
    /**
     * {@inheritdoc}
     */
    public function getTransactionType()
    {
        return Option\Transaction::AUTHORIZE;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureRequestOptions()
    {
        $this->resolver
            ->addOption(new Option\Amount())
            ->addOption(new Option\Currency())
            ->addOption(new Option\SolutionId())
            ->addOption(new Option\DataDescriptor())
            ->addOption(new Option\DataValue());

        return $this;
    }
}
