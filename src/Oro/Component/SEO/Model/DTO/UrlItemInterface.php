<?php

namespace Oro\Component\SEO\Model\DTO;

use Doctrine\Common\Collections\Collection;

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

    /**
     * @return Collection|HrefLanguageLinkInterface[]
     */
    public function getLinks();
}
