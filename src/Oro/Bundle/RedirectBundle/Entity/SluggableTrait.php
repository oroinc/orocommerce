<?php

namespace Oro\Bundle\RedirectBundle\Entity;

trait SluggableTrait
{
    use LocalizedSlugPrototypeWithRedirectAwareTrait;
    use SlugAwareTrait;
}
