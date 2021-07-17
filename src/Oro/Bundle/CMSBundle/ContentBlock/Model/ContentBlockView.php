<?php

namespace Oro\Bundle\CMSBundle\ContentBlock\Model;

use Doctrine\Common\Collections\Collection;

/**
 * Model for Content Block
 */
class ContentBlockView
{
    /**
     * @var string
     */
    private $alias;

    /**
     * @var Collection
     */
    private $titles;

    /**
     * @var bool
     */
    private $enabled;

    /**
     * @var string
     */
    private $content;

    /**
     * @var string
     */
    private $contentStyle;

    public function __construct(
        string $alias,
        Collection $titles,
        bool $enabled,
        string $content,
        string $contentStyle
    ) {
        $this->alias = $alias;
        $this->titles = $titles;
        $this->enabled = $enabled;
        $this->content = $content;
        $this->contentStyle = $contentStyle;
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

    /**
     * @return string
     */
    public function getContentStyle()
    {
        return $this->contentStyle;
    }
}
