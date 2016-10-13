<?php

namespace Oro\Bundle\WebCatalogBundle\Model;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;

/**
 * @method File getImage()
 * @method ContentNode setImage(File $image)
 * @method LocalizedFallbackValue getTitle(Localization $localization = null)
 * @method LocalizedFallbackValue getSlug(Localization $localization = null)
 */
class ExtendContentNode
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
