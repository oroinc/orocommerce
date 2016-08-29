<?php

namespace Oro\Bundle\WebsiteBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\WebsiteBundle\Entity\Website;

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
        $websites[] = $this->registry->getManagerForClass('OroWebsiteBundle:Website')
            ->getRepository('OroWebsiteBundle:Website')
            ->getDefaultWebsite();
        
        return $websites;
    }
}
