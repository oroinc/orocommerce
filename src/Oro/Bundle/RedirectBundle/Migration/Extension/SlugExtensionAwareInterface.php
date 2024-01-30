<?php

namespace Oro\Bundle\RedirectBundle\Migration\Extension;

/**
 * This interface should be implemented by migrations that depend on {@see SlugExtension}.
 */
interface SlugExtensionAwareInterface
{
    public function setSlugExtension(SlugExtension $extension);
}
