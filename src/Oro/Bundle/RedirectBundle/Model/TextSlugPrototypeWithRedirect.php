<?php

namespace Oro\Bundle\RedirectBundle\Model;

class TextSlugPrototypeWithRedirect
{
    /**
     * @var string
     */
    private $textSlugPrototype;

    /**
     * @var bool
     */
    private $createRedirect;

    /**
     * @param string $textSlugPrototype
     * @param bool $createRedirect
     */
    public function __construct(&$textSlugPrototype = null, $createRedirect = true)
    {
        $this->textSlugPrototype = &$textSlugPrototype;
        $this->createRedirect = $createRedirect;
    }

    /**
     * @return string
     */
    public function getTextSlugPrototype()
    {
        return $this->textSlugPrototype;
    }

    /**
     * @param string $textSlugPrototype
     * @return $this
     */
    public function setTextSlugPrototype($textSlugPrototype)
    {
        $this->textSlugPrototype = $textSlugPrototype;

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
