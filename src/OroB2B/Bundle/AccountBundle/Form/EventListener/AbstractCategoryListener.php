<?php

namespace OroB2B\Bundle\AccountBundle\Form\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;

use Doctrine\ORM\EntityRepository;

use OroB2B\Bundle\AccountBundle\Entity\Repository\AccountCategoryVisibilityRepository;
use OroB2B\Bundle\AccountBundle\Entity\Repository\AccountGroupCategoryVisibilityRepository;

abstract class AbstractCategoryListener
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var string */
    protected $categoryVisibilityClass;

    /** @var string */
    protected $accountCategoryVisibilityClass;

    /** @var string */
    protected $accountGroupCategoryVisibilityClass;

    /** @var AccountCategoryVisibilityRepository */
    protected $accountCategoryVisibilityRepository;

    /** @var AccountGroupCategoryVisibilityRepository */
    protected $accountGroupCategoryVisibilityRepository;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param string $categoryVisibilityClass
     */
    public function setCategoryVisibilityClass($categoryVisibilityClass)
    {
        $this->categoryVisibilityClass = $categoryVisibilityClass;
    }

    /**
     * @param string $accountCategoryVisibilityClass
     */
    public function setAccountCategoryVisibilityClass($accountCategoryVisibilityClass)
    {
        $this->accountCategoryVisibilityClass = $accountCategoryVisibilityClass;
    }

    /**
     * @param string $accountGroupCategoryVisibilityClass
     */
    public function setAccountGroupCategoryVisibilityClass($accountGroupCategoryVisibilityClass)
    {
        $this->accountGroupCategoryVisibilityClass = $accountGroupCategoryVisibilityClass;
    }

    /**
     * @return AccountCategoryVisibilityRepository
     */
    protected function getAccountCategoryVisibilityRepository()
    {
        if (!$this->accountCategoryVisibilityRepository) {
            $this->accountCategoryVisibilityRepository = $this->registry
                ->getManagerForClass($this->accountCategoryVisibilityClass)
                ->getRepository($this->accountCategoryVisibilityClass);
        }

        return $this->accountCategoryVisibilityRepository;
    }

    /**
     * @return AccountGroupCategoryVisibilityRepository
     */
    protected function getAccountGroupCategoryVisibilityRepository()
    {
        if (!$this->accountGroupCategoryVisibilityRepository) {
            $this->accountGroupCategoryVisibilityRepository = $this->registry
                ->getManagerForClass($this->accountGroupCategoryVisibilityClass)
                ->getRepository($this->accountGroupCategoryVisibilityClass);
        }

        return $this->accountGroupCategoryVisibilityRepository;
    }

    /**
     * @param string $className
     * @return EntityRepository
     */
    protected function getEntityRepository($className)
    {
        return $this->registry->getManagerForClass($className)->getRepository($className);
    }

    /**
     * @param object $object
     * @return EntityManager
     */
    protected function getEntityManager($object)
    {
        return $this->registry->getManagerForClass(ClassUtils::getClass($object));
    }
}
