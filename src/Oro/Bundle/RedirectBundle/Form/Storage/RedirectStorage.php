<?php

namespace Oro\Bundle\RedirectBundle\Form\Storage;

use Oro\Bundle\RedirectBundle\Model\PrefixWithRedirect;

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
