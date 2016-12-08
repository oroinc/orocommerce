<?php

namespace Oro\Bundle\RedirectBundle\Migration\Extension;

interface SlugExtensionAwareInterface
{
    /**
     * @param SlugExtension $extension
     */
    public function setSlugExtension(SlugExtension $extension);
}
