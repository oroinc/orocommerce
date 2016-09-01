<?php

namespace Oro\Bundle\WebsiteBundle\Manager;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class WebsiteManager
{
    /**
     * @var ManagerRegistry
     */
    protected $managerRegistry;

    /**
     * @var FrontendHelper
     */
    protected $frontendHelper;

    /**
     * @var Website
     */
    protected $currentWebsite;

    /**
     * @param ManagerRegistry $managerRegistry
     * @param FrontendHelper $frontendHelper
     */
    public function __construct(ManagerRegistry $managerRegistry, FrontendHelper $frontendHelper)
    {
        $this->managerRegistry = $managerRegistry;
        $this->frontendHelper = $frontendHelper;
    }

    /**
     * @return Website
     */
    public function getCurrentWebsite()
    {
        if (!$this->frontendHelper->isFrontendRequest()) {
            return null;
        }

        return $this->getResolvedWebsite();
    }

    /**
     * @return Website
     */
    public function getDefaultWebsite()
    {
        return $this->getEntityManager()
            ->getRepository(Website::class)
            ->getDefaultWebsite();
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->managerRegistry->getManagerForClass(Website::class);
    }

    /**
     * @return Website
     */
    protected function getResolvedWebsite()
    {
        if (!$this->currentWebsite) {
            $this->currentWebsite = $this->getDefaultWebsite();
        }

        return $this->currentWebsite;
    }
}
