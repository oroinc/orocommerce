<?php

namespace OroB2B\Bundle\AccountBundle\Form\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use Symfony\Component\Form\FormInterface;

use OroB2B\Bundle\AccountBundle\Entity\AccountAwareInterface;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroupAwareInterface;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;

abstract class VisibilityAbstractListener
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
        $targetEntityField = $form->getConfig()->getOption('targetEntityField');
        $criteria = [
            $targetEntityField => $targetEntity
        ];

        $visibilityClassName = $form->getConfig()->getOption($field.'Class');
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
     * @return VisibilityInterface
     */
    protected function createFormFieldData($form, $field)
    {
        $targetEntity = $form->getData();
        $visibilityClassName = $form->getConfig()->getOption($field.'Class');
        /** @var VisibilityInterface $visibility */
        $visibility = new $visibilityClassName();
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
