<?php

namespace Oro\Bundle\WebCatalogBundle\Model;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;

/**
 * Model class which allows to extend the ContentNode entity.
 *
 * @method LocalizedFallbackValue getTitle(Localization $localization = null)
 * @method LocalizedFallbackValue getDefaultTitle()
 * @method LocalizedFallbackValue getSlugPrototype(Localization $localization = null)
 * @method LocalizedFallbackValue getDefaultSlugPrototype()
 * @method setDefaultTitle($title)
 * @method setDefaultSlugPrototype($slug)
 * @method $this cloneLocalizedFallbackValueAssociations()
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
