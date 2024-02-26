<?php

namespace Oro\Bundle\RedirectBundle\Entity;

/**
* Sluggable trait
*
*/
trait SluggableTrait
{
    use LocalizedSlugPrototypeWithRedirectAwareTrait;
    use SlugAwareTrait;
}
