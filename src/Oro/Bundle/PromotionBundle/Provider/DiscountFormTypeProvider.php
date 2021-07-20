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
     * @var string
     */
    private $defaultDiscountFormType;

    /**
     * @param string $discountType
     * @param string $discountFormType
     */
    public function addFormType($discountType, $discountFormType)
    {
        $this->formTypes[$discountType] = $discountFormType;
    }

    /**
     * @param string $defaultDiscountFormType
     */
    public function setDefaultFormType($defaultDiscountFormType)
    {
        $this->defaultDiscountFormType = $defaultDiscountFormType;
    }

    public function getDefaultFormType(): string
    {
        if (!$this->defaultDiscountFormType) {
            throw new \LogicException('Default discount type is not provided.');
        }

        return $this->defaultDiscountFormType;
    }

    /**
     * @param string $discountType
     * @return string|null
     */
    public function getFormType($discountType)
    {
        return $this->formTypes[$discountType] ?? null;
    }

    public function getFormTypes(): array
    {
        return $this->formTypes;
    }
}
