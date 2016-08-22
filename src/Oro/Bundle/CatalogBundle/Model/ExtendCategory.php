<?php

namespace Oro\Bundle\CatalogBundle\Model;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\CatalogBundle\Entity\Category;

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
