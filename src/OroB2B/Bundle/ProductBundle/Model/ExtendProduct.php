<?php

namespace OroB2B\Bundle\ProductBundle\Model;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;

use OroB2B\Bundle\ProductBundle\Entity\Product;

/**
 * @method AbstractEnumValue getInventoryStatus()
 * @method Product setInventoryStatus(AbstractEnumValue $enumId)
 * @method AbstractEnumValue getVisibility()
 * @method Product setVisibility(AbstractEnumValue $enumId)
 * @method File getImage()
 * @method Product setImage(File $image)
 */
class ExtendProduct
{
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
}
