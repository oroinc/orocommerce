<?php

namespace Oro\Bundle\RedirectBundle\Migration\Extension;

interface SlugExtensionAwareInterface
{
    public function setSlugExtension(SlugExtension $extension);
}
