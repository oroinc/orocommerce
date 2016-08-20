<?php

namespace Oro\Bundle\WebsiteBundle\Entity;

interface WebsiteAwareInterface
{
    /**
     * @return Website
     */
    public function getWebsite();

    /**
     * @param Website $website
     * @return $this
     */
    public function setWebsite(Website $website);
}
