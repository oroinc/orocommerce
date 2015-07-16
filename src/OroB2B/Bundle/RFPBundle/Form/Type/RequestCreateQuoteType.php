<?php

namespace OroB2B\Bundle\RFPBundle\Form\Type;

use Symfony\Component\Form\AbstractType;

class RequestCreateQuoteType extends AbstractType
{
    const NAME = 'orob2b_rfp_request_create_quote';

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }
}
