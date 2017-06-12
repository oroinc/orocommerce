<?php

namespace Oro\Bundle\PromotionBundle\Provider;

/**
 * This provider accumulates mappings between discount types and theirs form types.
 */
class DiscountFormTypeProvider
{
    /**
     * @var array
     */
    private $formTypes = [];

    /**
     * @param string $discountType
     * @param string $discountFormType
     */
    public function addFormType($discountType, $discountFormType)
    {
        $this->formTypes[$discountType] = $discountFormType;
    }

    /**
     * @param string $discountType
     * @return string|null
     */
    public function getFormType($discountType)
    {
        return $this->formTypes[$discountType] ?? null;
    }

    /**
     * @return array
     */
    public function getFormTypes()
    {
        return $this->formTypes;
    }
}
