<?php

namespace Oro\Bundle\VisibilityBundle\Entity\Visibility\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;

class RepositoryHolder
{
    /**
     * @var EntityRepository
     */
    protected $repository;

    /**
     * @param EntityRepository $repository
     */
    public function __construct(EntityRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param ScopeManager $scopeManager
     */
    public function setScopeManager(ScopeManager $scopeManager)
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $this->repository->setScopeManager($scopeManager);
    }

    /**
     * @param InsertFromSelectQueryExecutor $insertExecutor
     */
    public function setInsertExecutor(InsertFromSelectQueryExecutor $insertExecutor)
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $this->repository->setInsertExecutor($insertExecutor);
    }

    /**
     * @return EntityRepository
     */
    public function getRepository()
    {
        return $this->repository;
    }
}
