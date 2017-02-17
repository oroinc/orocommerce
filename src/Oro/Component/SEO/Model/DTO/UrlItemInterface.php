<?php

namespace Oro\Component\SEO\Model\DTO;

interface UrlItemInterface
{
    const ROOT_NODE_ELEMENT = 'url';

    /**
     * @return string
     */
    public function getLocation();

    /**
     * @return null|string
     */
    public function getChangeFrequency();

    /**
     * @return null|float
     */
    public function getPriority();

    /**
     * @return null|\DateTime
     */
    public function getLastModification();
}
