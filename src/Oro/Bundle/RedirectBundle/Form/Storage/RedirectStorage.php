<?php

namespace Oro\Bundle\RedirectBundle\Form\Storage;

use Oro\Bundle\RedirectBundle\Model\PrefixWithRedirect;

class RedirectStorage
{
    /**
     * @var PrefixWithRedirect
     */
    protected $prefix;

    /**
     * @param string $key
     * @param PrefixWithRedirect $prefix
     * @return $this
     */
    public function addPrefix($key, PrefixWithRedirect $prefix)
    {
        $this->prefix[$key] = $prefix;

        return $this;
    }

    /**
     * @param string $key
     * @return PrefixWithRedirect
     */
    public function getPrefixByKey($key)
    {
        return $this->prefix[$key];
    }
}
