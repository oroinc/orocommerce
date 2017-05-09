<?php

namespace Oro\Bundle\CMSBundle\ContentBlock\Model;

use Doctrine\Common\Collections\Collection;

class ContentBlockView
{
    /** @var string */
    protected $alias;

    /** @var Collection */
    protected $titles;

    /** @var bool */
    protected $enabled;

    /** @var string */
    protected $content;

    /**
     * @param string     $alias
     * @param Collection $titles
     * @param bool       $enabled
     * @param string     $content
     */
    public function __construct(
        $alias,
        Collection $titles,
        $enabled,
        $content
    ) {
        $this->alias = $alias;
        $this->titles = $titles;
        $this->enabled = $enabled;
        $this->content = $content;
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @return Collection
     */
    public function getTitles()
    {
        return $this->titles;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }
}
