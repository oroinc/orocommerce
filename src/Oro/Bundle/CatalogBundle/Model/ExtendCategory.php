<?php

namespace Oro\Bundle\CatalogBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;

/**
 * @method File getSmallImage()
 * @method Category setSmallImage(File $smallImage)
 * @method File getLargeImage()
 * @method Category setLargeImage(File $largeImage)
 * @method LocalizedFallbackValue getTitle(Localization $localization = null)
 * @method LocalizedFallbackValue getDefaultTitle()
 * @method LocalizedFallbackValue getShortDescription(Localization $localization = null)
 * @method LocalizedFallbackValue getDefaultShortDescription()
 * @method LocalizedFallbackValue getLongDescription(Localization $localization = null)
 * @method LocalizedFallbackValue getDefaultLongDescription()
 * @method LocalizedFallbackValue getMetaTitle(Localization $localization = null)
 * @method LocalizedFallbackValue getMetaDescription(Localization $localization = null)
 * @method LocalizedFallbackValue getMetaKeyword(Localization $localization = null)
 * @method Category setProducts(ArrayCollection $value)
 * @method removeProduct(Product $value)
 * @method ArrayCollection getProducts()
 * @method addProduct(Product $value)

 */
class ExtendCategory
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
