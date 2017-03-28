<?php

namespace Oro\Component\SEO\Model\DTO;

interface UrlItemInterface
{
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
     * @return null|string
     */
    public function getLastModification();
}
