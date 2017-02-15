<?php

namespace Oro\Component\SEO\Model\DTO;

interface HrefLanguageLinkInterface
{
    const HREF_LANGUAGE_DEFAULT = 'x';
    const REL_ALTERNATE = 'alternate';

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
