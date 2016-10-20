<?php

namespace Oro\Bundle\VisibilityBundle\Form\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\CustomerBundle\Entity\AccountAwareInterface;
use Oro\Bundle\CustomerBundle\Entity\AccountGroupAwareInterface;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\WebsiteBundle\Entity\WebsiteAwareInterface;
use Symfony\Component\Form\FormInterface;

abstract class AbstractVisibilityListener
{
    /** @var ManagerRegistry */
    protected $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param FormInterface $form
     * @param string $field
     * @return VisibilityInterface|VisibilityInterface[]
     */
    protected function findFormFieldData($form, $field)
    {
        $targetEntity = $form->getData();
        $config = $form->getConfig();
        $targetEntityField = $config->getOption('targetEntityField');
        $criteria = [];
        $criteria[$targetEntityField] = $targetEntity;

        if ($website = $config->getOption('website')) {
            $criteria['website'] = $website;
        }
        $visibilityClassName = $form->getConfig()->getOption($field . 'Class');
        if ($field === 'all') {
            return $this->getEntityRepository($visibilityClassName)->findOneBy($criteria);
        } else {
            return $this->mapVisibilitiesById($this->getEntityRepository($visibilityClassName)->findBy($criteria));
        }
    }

    /**
     * @param array $visibilities
     * @return VisibilityInterface[]
     */
    protected function mapVisibilitiesById($visibilities)
    {
        $visibilitiesById = [];
        /** @var VisibilityInterface|AccountGroupAwareInterface|AccountAwareInterface $visibilityEntity */
        foreach ($visibilities as $visibilityEntity) {
            if ($visibilityEntity instanceof AccountGroupAwareInterface) {
                $visibilitiesById[$visibilityEntity->getAccountGroup()->getId()] = $visibilityEntity;
            } elseif ($visibilityEntity instanceof AccountAwareInterface) {
                $visibilitiesById[$visibilityEntity->getAccount()->getId()] = $visibilityEntity;
            }
        }

        return $visibilitiesById;
    }

    /**
     * @param FormInterface $form
     * @param string $field
     * @return VisibilityInterface|WebsiteAwareInterface
     */
    protected function createFormFieldData($form, $field)
    {
        $targetEntity = $form->getData();
        $config = $form->getConfig();
        $visibilityClassName = $config->getOption($field . 'Class');
        /** @var VisibilityInterface|WebsiteAwareInterface $visibility */
        $visibility = new $visibilityClassName();
        if ($visibility instanceof WebsiteAwareInterface) {
            $visibility->setWebsite($config->getOption('website'));
        }
        $visibility->setTargetEntity($targetEntity);

        return $visibility;
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
     * @param Object $targetEntity
     * @return EntityManager
     */
    protected function getEntityManager($targetEntity)
    {
        return $this->registry->getManagerForClass(ClassUtils::getClass($targetEntity));
    }
}
