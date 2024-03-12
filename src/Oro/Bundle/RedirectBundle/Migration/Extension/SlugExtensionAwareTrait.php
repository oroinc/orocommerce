<?php

namespace Oro\Bundle\RedirectBundle\Migration\Extension;

/**
 * This trait can be used by migrations that implement {@see SlugExtensionAwareInterface}.
 */
trait SlugExtensionAwareTrait
{
    private SlugExtension $slugExtension;

    public function setSlugExtension(SlugExtension $extension): void
    {
        $this->slugExtension = $extension;
    }
}
