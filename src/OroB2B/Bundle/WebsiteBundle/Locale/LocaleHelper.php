<?php

namespace OroB2B\Bundle\WebsiteBundle\Locale;

use Doctrine\Common\Persistence\ManagerRegistry;

use OroB2B\Bundle\WebsiteBundle\Entity\Locale;

class LocaleHelper
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var string
     */
    protected $entityClass;

    /**
     * LocaleHelper constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param string $entityClass
     */
    public function setEntityClass($entityClass)
    {
        $this->entityClass = $entityClass;
    }

    /**
     * @return Locale
     */
    public function getCurrentLocale()
    {
        return $this->getLocale('en');
    }

    /**
     * @param string $code
     * @return null|Locale
     */
    public function getLocale($code)
    {
        return $this->getRepository()->findOneByCode($code);
    }

    /**
     * @return Locale[]
     */
    public function getAll()
    {
        return $this->getRepository()->findAll();
    }

    /**
     * @return \OroB2B\Bundle\WebsiteBundle\Entity\Repository\LocaleRepository
     */
    protected function getRepository()
    {
        $repo = $this->registry
            ->getManagerForClass($this->entityClass)
            ->getRepository($this->entityClass);

        return $repo;
    }
}
