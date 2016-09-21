<?php

namespace Oro\Bundle\ScopeBundle\Manager;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Component\PropertyAccess\PropertyAccessor;

class ScopeManager
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var
     */
    protected $entityFieldProvider;

    /**
     * @var PropertyAccessor
     */
    protected $propertyAccessor;

    /**
     * @param ManagerRegistry $registry
     * @param EntityFieldProvider $entityFieldProvider
     */
    public function __construct(ManagerRegistry $registry, EntityFieldProvider $entityFieldProvider)
    {
        $this->registry = $registry;
        $this->entityFieldProvider = $entityFieldProvider;
    }

    /**
     * @var ScopeProviderInterface[]
     */
    protected $providers = [];

    /**
     * @var array
     */
    protected $baseCriteria = null;

    /**
     * @param string $scopeType
     * @param array|object $context
     * @return Scope
     */
    public function find($scopeType, $context = null)
    {
        $criteria = $this->getCriteria($scopeType, $context);

        return $this->registry->getManagerForClass(Scope::class)
            ->getRepository(Scope::class)
            ->findOneBy($criteria);
    }

    /**
     * @param string $scopeType
     * @param array|object $context
     * @return Scope
     */
    public function findOrCreate($scopeType, $context = null)
    {
        $criteria = $this->getCriteria($scopeType, $context);

        $scope = $this->registry->getManagerForClass(Scope::class)
            ->getRepository(Scope::class)
            ->findOneBy($criteria);
        if (!$scope) {
            $scope = new Scope();
            $propertyAccessor = $this->getPropertyAccessor();
            foreach ($criteria as $fieldName => $value) {
                if ($value !== null) {
                    $propertyAccessor->setValue($scope, $fieldName, $value);
                }
            }

            $manager = $this->registry->getManagerForClass(Scope::class);
            $manager->persist($scope);
            $manager->flush($scope);
        }

        return $scope;
    }

    /**
     * @param string $scopeType
     * @param ScopeProviderInterface $provider
     */
    public function addProvider($scopeType, ScopeProviderInterface $provider)
    {
        $this->providers[$scopeType][] = $provider;
    }

    /**
     * @param $scopeType
     * @param $context
     * @return array
     */
    protected function getCriteria($scopeType, $context = null)
    {
        $criteria = $this->getBaseCriteria();
        $providers = empty($this->providers[$scopeType]) ? [] : $this->providers[$scopeType];
        foreach ($providers as $provider) {
            $criteria = array_merge($criteria, $provider->getCriteria($scopeType, $context));
        }

        return $criteria;
    }

    /**
     * @return array
     */
    protected function getBaseCriteria()
    {
        if ($this->baseCriteria === null) {
            $this->baseCriteria = [];
            $fields = $this->entityFieldProvider->getRelations(Scope::class);
            foreach ($fields as $field) {
                $this->baseCriteria[$field['name']] = null;
            }
        }

        return $this->baseCriteria;
    }

    /**
     * @return PropertyAccessor
     */
    protected function getPropertyAccessor()
    {
        if (null === $this->propertyAccessor) {
            $this->propertyAccessor = new PropertyAccessor();
        }

        return $this->propertyAccessor;
    }
}
