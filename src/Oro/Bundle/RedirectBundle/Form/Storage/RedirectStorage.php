<?php

namespace Oro\Bundle\RedirectBundle\Form\Storage;

use Oro\Bundle\RedirectBundle\Model\PrefixWithRedirect;

/**
 * Storage for managing URL prefix configurations with redirect creation preferences.
 *
 * This class provides temporary storage for {@see PrefixWithRedirect} objects during form processing,
 * allowing the system to track which URL prefixes should be applied to slugs and whether automatic redirects
 * should be created when those prefixes change. It acts as a registry that can be populated during form submission
 * and queried when generating or updating slugs.
 */
class RedirectStorage
{
    /**
     * @var PrefixWithRedirect[]|array
     */
    protected $storage = [];

    /**
     * @param string $key
     * @param PrefixWithRedirect $prefix
     * @return $this
     */
    public function addPrefix($key, PrefixWithRedirect $prefix)
    {
        $this->storage[$key] = $prefix;

        return $this;
    }

    /**
     * @param string $key
     * @return PrefixWithRedirect|null
     */
    public function getPrefixByKey($key)
    {
        if (array_key_exists($key, $this->storage)) {
            return $this->storage[$key];
        }

        return null;
    }
}
