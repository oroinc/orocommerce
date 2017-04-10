<?php

namespace Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Request;

use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Option;

class CaptureRequest extends AbstractRequest
{
    /** {@inheritdoc} */
    public function getTransactionType()
    {
        return Option\Transaction::CAPTURE;
    }

    /** {@inheritdoc} */
    protected function configureRequestOptions()
    {
        parent::configureRequestOptions();
        $this->addOption(new Option\OriginalTransaction());
        return $this;
    }
}
