<?php

namespace Oro\Bundle\CatalogBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\CategoryLongDescription;
use Oro\Bundle\CatalogBundle\Entity\CategoryShortDescription;
use Oro\Bundle\CatalogBundle\Entity\CategoryTitle;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Extend class for Category entity.
 *
 * @method File getSmallImage()
 * @method Category setSmallImage(File $smallImage)
 * @method File getLargeImage()
 * @method Category setLargeImage(File $largeImage)
 * @method CategoryTitle getTitle(Localization $localization = null)
 * @method CategoryTitle getDefaultTitle()
 * @method CategoryShortDescription getShortDescription(Localization $localization = null)
 * @method CategoryShortDescription getDefaultShortDescription()
 * @method CategoryLongDescription getLongDescription(Localization $localization = null)
 * @method CategoryLongDescription getDefaultLongDescription()
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
