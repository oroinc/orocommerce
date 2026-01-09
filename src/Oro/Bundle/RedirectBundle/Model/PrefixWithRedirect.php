<?php

namespace Oro\Bundle\RedirectBundle\Model;

/**
 * Represents a URL prefix with associated redirect creation preference.
 *
 * This model object encapsulates a URL prefix string and a boolean flag indicating whether
 * automatic redirects should be created when the prefix is applied to slug URLs. It is used
 * to pass prefix configuration through the slug generation system.
 */
class PrefixWithRedirect
{
    /**
     * @var string
     */
    private $prefix;

    /**
     * @var bool
     */
    private $createRedirect;

    /**
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * @param string $prefix
     * @return $this
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getCreateRedirect()
    {
        return $this->createRedirect;
    }

    /**
     * @param boolean $createRedirect
     * @return $this
     */
    public function setCreateRedirect($createRedirect)
    {
        $this->createRedirect = $createRedirect;

        return $this;
    }
}
