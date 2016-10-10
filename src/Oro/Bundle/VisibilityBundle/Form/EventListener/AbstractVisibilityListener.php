<?php

namespace Oro\Bundle\VisibilityBundle\Form\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Entity\AccountGroup;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\VisibilityBundle\Model\Exception\InvalidArgumentException;
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
        $context = $this->getFormScopeContext($form->get($field), $type);
        $criteria = $this->scopeManager->getCriteria($type, $context);
        $criteria->applyWhere($qb, 'scope');

        if ($field === 'all') {
            return $qb->getQuery()->getOneOrNullResult();
        } else {
            return $this->mapVisibilitiesById($qb->getQuery()->getResult());
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
        $visibilitiesById = [];
        /** @var VisibilityInterface $visibilityEntity */
        foreach ($visibilities as $visibilityEntity) {
            $scope = $visibilityEntity->getScope();

            /** @var Account $account */
            /** @noinspection PhpUndefinedMethodInspection - field added through entity extend */
            $account = $scope->getAccount();

            /** @var AccountGroup $accountGroup */
            /** @noinspection PhpUndefinedMethodInspection - field added through entity extend */
            $accountGroup = $scope->getAccountGroup();

            if (null !== $accountGroup) {
                $visibilitiesById[$accountGroup->getId()] = $visibilityEntity;
            } elseif (null !== $account) {
                $visibilitiesById[$account->getId()] = $visibilityEntity;
            }
        }

        return $visibilitiesById;
    }

    /**
     * @param FormInterface $form
     * @param string $field
     * @param null|object $fieldData
     * @return VisibilityInterface
     */
    protected function createFormFieldData($form, $field, $fieldData = null)
    {
        $config = $form->getConfig();

        $visibilityClassName = $config->getOption($field.'Class');

        if ($config->hasOption('scope_id')) {
            $rootScope = $config->getOption('scope');
        } else {
            $rootScope = $this->scopeManager->findDefaultScope();
        }
        $scopeType = $this->getVisibilityScopeType($form, $field);

        $context = $this->scopeManager->getCriteriaByScope(
            $rootScope,
            $scopeType
        )->toArray();
        if (null !== $fieldData && array_key_exists($field, $context)) {
            $context[$field] = $fieldData;
        }

        /** @var VisibilityInterface $visibility */
        $visibility = new $visibilityClassName();
        $scope = $this->scopeManager->findOrCreate(
            $scopeType,
            $context
        );
        $visibility->setScope($scope);
        $visibility->setTargetEntity($form->getData());

        return $visibility;
    }

    /**
     * @param string $className
     * @return EntityRepository|ObjectRepository
     */
    protected function getEntityRepository($className)
    {
        return $this->registry->getManagerForClass($className)->getRepository($className);
    }

    /**
     * @param Object $targetEntity
     * @return EntityManager|ObjectManager
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
