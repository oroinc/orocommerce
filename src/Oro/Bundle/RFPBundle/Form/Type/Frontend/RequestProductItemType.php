<?php

namespace Oro\Bundle\RFPBundle\Form\Type\Frontend;

use Oro\Bundle\RFPBundle\Form\Type\RequestProductItemType as BaseRequestProductItemType;

/**
 * Form type for RequestProductItem on the frontend
 */
class RequestProductItemType extends BaseRequestProductItemType
{
    const BLOCK_PREFIX = 'oro_rfp_frontend_request_product_item';

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::BLOCK_PREFIX;
    }
}
