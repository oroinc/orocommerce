<?php

namespace Oro\Bundle\SEOBundle\Model\DTO;

use Oro\Component\SEO\Model\DTO\UrlItemLinkInterface;

class UrlItemLink implements UrlItemLinkInterface
{
    /**
     * @var string
     */
    private $rel;

    /**
     * @var string
     */
    private $hrefLanguage;

    /**
     * @var string
     */
    private $href;

    /**
     * @param string $rel
     * @param string $hrefLanguage
     * @param string $href
     */
    public function __construct($rel = null, $hrefLanguage = null, $href = null)
    {
        $this->rel = $rel;
        $this->hrefLanguage = $hrefLanguage;
        $this->href = $href;
    }

    /**
     * {@inheritdoc}
     */
    public function getRel()
    {
        return $this->rel;
    }

    /**
     * {@inheritdoc}
     */
    public function getHrefLanguage()
    {
        return $this->hrefLanguage;
    }

    /**
     * {@inheritdoc}
     */
    public function getHref()
    {
        return $this->href;
    }
}
