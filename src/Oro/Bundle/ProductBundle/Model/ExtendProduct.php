<?php

namespace Oro\Bundle\ProductBundle\Model;

use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * @method AbstractEnumValue getInventoryStatus()
 * @method Product setInventoryStatus(AbstractEnumValue $enumId)
 * @method LocalizedFallbackValue getName(Localization $localization = null)
 * @method LocalizedFallbackValue getDefaultName()
 * @method LocalizedFallbackValue getDescription(Localization $localization = null)
 * @method LocalizedFallbackValue getDefaultDescription()
 * @method LocalizedFallbackValue getShortDescription(Localization $localization = null)
 * @method LocalizedFallbackValue getDefaultShortDescription()
 * @method LocalizedFallbackValue getMetaTitle(Localization $localization = null)
 * @method LocalizedFallbackValue getMetaDescription(Localization $localization = null)
 * @method LocalizedFallbackValue getMetaKeyword(Localization $localization = null)
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
