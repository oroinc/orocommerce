<?php

namespace Oro\Bundle\SEOBundle\Model\DTO;

use Oro\Component\SEO\Model\DTO\HrefLanguageLinkInterface;

class HrefLanguageLink implements HrefLanguageLinkInterface
{
    /**
     * @var string
     */
    private $href;

    /**
     * @var string
     */
    private $hrefLanguage;

    /**
     * @var string
     */
    private $rel;

    /**
     * @param string $rel
     * @param string $hrefLanguage
     * @param string $href
     */
    public function __construct(
        $href,
        $hrefLanguage = HrefLanguageLinkInterface::HREF_LANGUAGE_DEFAULT,
        $rel = HrefLanguageLinkInterface::REL_ALTERNATE
    ) {
        $this->href = $href;
        $this->hrefLanguage = $hrefLanguage;
        $this->rel = $rel;
    }

    /**
     * {@inheritdoc}
     */
    public function getHref()
    {
        return $this->href;
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
    public function getRel()
    {
        return $this->rel;
    }
}
