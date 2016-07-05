<?php

namespace OroB2B\Bundle\WebsiteBundle\Provider;

use OroB2B\Bundle\WebsiteBundle\Entity\Website;

interface WebsiteProviderInterface
{
    /**
     * @return Website[]
     */
    public function getWebsites();
}
