<?php

namespace Oro\Bundle\ProductBundle\Model;

use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductDescription;
use Oro\Bundle\ProductBundle\Entity\ProductName;
use Oro\Bundle\ProductBundle\Entity\ProductShortDescription;

/**
 * Extend class for Product entity.
 *
 * @method AbstractEnumValue getInventoryStatus()
 * @method Product setInventoryStatus(AbstractEnumValue $enumId)
 * @method ProductName getName(Localization $localization = null)
 * @method ProductName getDefaultName()
 * @method setDefaultName(string $value)
 * @method LocalizedFallbackValue getDefaultSlugPrototype()
 * @method setDefaultSlugPrototype(string $value)
 * @method ProductDescription getDescription(Localization $localization = null)
 * @method ProductDescription getDefaultDescription()
 * @method ProductShortDescription getShortDescription(Localization $localization = null)
 * @method ProductShortDescription getDefaultShortDescription()
 * @method LocalizedFallbackValue getMetaTitle(Localization $localization = null)
 * @method LocalizedFallbackValue getMetaDescription(Localization $localization = null)
 * @method LocalizedFallbackValue getMetaKeyword(Localization $localization = null)
 * @method EntityFieldFallbackValue getPageTemplate()
 * @method ExtendProduct setPageTemplate(EntityFieldFallbackValue $pageTemplate)
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
