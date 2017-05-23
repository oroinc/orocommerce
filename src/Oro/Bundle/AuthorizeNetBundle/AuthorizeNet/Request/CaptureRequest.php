<?php

namespace Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Request;

use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Option;

class CaptureRequest extends AbstractRequest
{
    /**
     * {@inheritdoc}
     */
    public function getTransactionType()
    {
        return Option\Transaction::CAPTURE;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureRequestOptions()
    {
        $this
            ->addOption(new Option\Amount())
            ->addOption(new Option\Currency())
            ->addOption(new Option\SolutionId())
            ->addOption(new Option\OriginalTransaction());

        return $this;
    }
}
