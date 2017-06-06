<?php

namespace Oro\Bundle\PromotionBundle\Provider;

class PromotionDiscountConfigurationFormTypeProvider
{
    /**
     * @var array
     */
    protected $discountTypeToFormType = [];

    /**
     * @param string $discountType
     * @param string $formType
     */
    public function addType($discountType, $formType)
    {
        $this->discountTypeToFormType[$discountType] = $formType;
    }
}
