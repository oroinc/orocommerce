<?php

namespace Oro\Bundle\VisibilityBundle\Form\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\AccountBundle\Entity\AccountAwareInterface;
use Oro\Bundle\AccountBundle\Entity\AccountGroupAwareInterface;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\VisibilityBundle\Model\Exception\InvalidArgumentException;
use Oro\Bundle\WebsiteBundle\Entity\WebsiteAwareInterface;
use Symfony\Component\Form\FormInterface;

abstract class AbstractVisibilityListener
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var ScopeManager
     */
    protected $scopeManager;

    /**
     * @param ManagerRegistry $registry
     * @param ScopeManager $scopeManager
     */
    public function __construct(ManagerRegistry $registry, ScopeManager $scopeManager)
    {
        $this->registry = $registry;
        $this->scopeManager = $scopeManager;
    }

    /**
     * @param FormInterface $form
     * @param string $field
     * @return VisibilityInterface|\Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface[]
     */
    protected function findFormFieldData($form, $field)
    {
        $targetEntity = $form->getData();
        $config = $form->getConfig();
        $targetEntityField = $config->getOption('targetEntityField');
        $visibilityClassName = $form->getConfig()->getOption($field.'Class');

        /** @var EntityManager $em */
        $em = $this->registry->getManagerForClass($visibilityClassName);
        $qb = $em->createQueryBuilder();
        $qb->select('scope, v')
            ->from($visibilityClassName, 'v')
            ->join('v.scope', 'scope')
            ->where(sprintf('v.%1$s = :%1$s', $targetEntityField))
            ->setParameter($targetEntityField, $targetEntity);

        $type = $this->getVisibilityScopeType($form, $field);
        $context = $this->getFormScopeContext($form, $type);
        $criteria = $this->scopeManager->getCriteria($type, $context);
        $criteria->applyWhere($qb, 'scope');

        if ($field === 'all') {
            return $qb->getQuery()->getOneOrNullResult();
        } else {
            $result = $qb->getQuery()->getResult();
            return $result;
        }
    }

    /**
     * @param FormInterface $form
     * @param string $type
     * @return array
     */
    protected function getFormScopeContext(FormInterface $form, $type)
    {
        $context = [];
        if ($form->getConfig()->hasOption('context')) {
            $context = $form->getConfig()->getOption('context');
        } elseif ($form->getConfig()->hasOption('scope')) {
            $scope = $form->getConfig()->getOption('scope');

            if ($scope instanceof Scope) {
                $context = $this->scopeManager->getCriteriaByScope($scope, $type)->toArray();
            }
        }

        $parentForm = $form->getParent();
        if (null !== $parentForm) {
            $context = array_replace($this->getFormScopeContext($parentForm, $type), $context);
        }

        return $context;
    }

    /**
     * @param array $visibilities
     * @return VisibilityInterface[]
     */
    protected function mapVisibilitiesById($visibilities)
    {
        // todo: BB-4506
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
        $visibilityClassName = $config->getOption($field.'Class');
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

    /**
     * @param FormInterface $form
     * @param $field
     * @return string
     */
    protected function getVisibilityScopeType(FormInterface $form, $field)
    {
        switch ($field) {
            case 'all':
                $className = $form->getConfig()->getOption('allClass');
                break;
            case 'account':
                $className = $form->getConfig()->getOption('accountClass');
                break;
            case 'accountGroup':
                $className = $form->getConfig()->getOption('accountGroupClass');
                break;
            default:
                throw new InvalidArgumentException();
        }

        return call_user_func([$className, 'getScopeType']);
    }
}
