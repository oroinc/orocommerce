<?php

namespace Oro\Component\SEO\Model\DTO;

interface UrlItemLinkInterface
{
    /**
     * @return string
     */
    public function getRel();

    /**
     * @return string
     */
    public function getHrefLanguage();

    /**
     * @return string
     */
    public function getHref();
}
