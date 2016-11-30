<?php

namespace Oro\Bundle\CustomerBundle\Doctrine;

use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Doctrine\RegistryInterface;

use Oro\Bundle\FrontendBundle\Request\FrontendHelper;

class DoctrineFiltersListener
{
    /**
     * @var RegistryInterface
     */
    protected $registry;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var FrontendHelper
     */
    protected $frontendHelper;

    /**
     * @param RegistryInterface $registry
     * @param FrontendHelper $frontendHelper
     */
    public function __construct(RegistryInterface $registry, FrontendHelper $frontendHelper)
    {
        $this->registry = $registry;
        $this->frontendHelper = $frontendHelper;
    }

    public function onRequest()
    {
        if ($this->frontendHelper->isFrontendRequest()) {
            $filters = $this->getEntityManager()->getFilters();
            /** @var SoftDeleteableFilter $filter */
            $filter = $filters->enable(SoftDeleteableFilter::FILTER_ID);
            $filter->setEm($this->getEntityManager());
        }
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        if (!$this->em) {
            $this->em = $this->registry->getManager();
        }

        return $this->em;
    }
}
