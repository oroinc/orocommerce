<?php

namespace OroB2B\Bundle\AccountBundle\Model;

use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;

class ExtendAccountCategoryVisibility
{
    /** @var AbstractEnumValue */
    protected $visibility;

    /**
     * Constructor
     *
     * The real implementation of this method is auto generated.
     *
     * IMPORTANT: If the derived class has own constructor it must call parent constructor.
     */
    public function __construct()
    {
    }

    /**
     * @param AbstractEnumValue|null $enumValue
     * @return $this
     */
    public function setVisibility(AbstractEnumValue $enumValue = null)
    {
        $this->visibility = $enumValue;

        return $this;
    }

    /**
     * @return AbstractEnumValue|null
     */
    public function getVisibility()
    {
        return $this->visibility;
    }
}
