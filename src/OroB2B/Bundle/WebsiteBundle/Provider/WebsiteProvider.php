<?php

namespace OroB2B\Bundle\WebsiteBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;

use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class WebsiteProvider implements WebsiteProviderInterface
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;
    
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @return Website[]
     */
    public function getWebsites()
    {
        $websites = [];
        $websites[] = $this->registry->getManagerForClass('OroB2BWebsiteBundle:Website')
            ->getRepository('OroB2BWebsiteBundle:Website')
            ->getDefaultWebsite();
        
        return $websites;
    }
}
