<?php

namespace OroB2B\Bundle\WebsiteBundle\Entity;

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
