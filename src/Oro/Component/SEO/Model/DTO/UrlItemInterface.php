<?php

namespace Oro\Component\SEO\Model\DTO;

interface UrlItemInterface extends SitemapItemInterface
{
    /**
     * @return null|string
     */
    public function getChangeFrequency();

    /**
     * @return null|float
     */
    public function getPriority();
}
