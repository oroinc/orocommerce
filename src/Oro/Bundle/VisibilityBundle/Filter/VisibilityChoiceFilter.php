<?php

namespace Oro\Bundle\VisibilityBundle\Filter;

use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Filter\ChoiceFilter;
use Symfony\Bridge\Doctrine\RegistryInterface;

class VisibilityChoiceFilter extends ChoiceFilter
{
    /**
     * @var RegistryInterface
     */
    protected $registry;

    /**
     * @param RegistryInterface $registry
     */
    public function setRegistry($registry)
    {
        $this->registry = $registry;
    }

    /**
     * We don't store records with default visibility value in database
     * To include this records when default option is selected additional "or" condition added
     *
     * {@inheritdoc}
     */
    protected function buildExpr(FilterDatasourceAdapterInterface $ds, $comparisonType, $fieldName, $data)
    {
        $expression = parent::buildExpr($ds, $comparisonType, $fieldName, $data);

        if ($ds instanceof OrmFilterDatasourceAdapter) {
            $qb = $ds->getQueryBuilder();

            $visibilityClass = $this->params['visibilityEntityClass'];
            $targetEntityClass = $this->params['targetEntityClass'];
            $targetEntityId = $qb->getParameter('target_entity_id')->getValue();

            if (!$visibilityClass || !$targetEntityClass || !$targetEntityId) {
                throw new \InvalidArgumentException("Required filter parameters missing");
            }

            $targetEntity = $this->getEntity($targetEntityId, $targetEntityClass);

            foreach ($data['value'] as $value) {
                if ($this->isDefaultValue($targetEntity, $value, $visibilityClass)) {
                    $expression = $this->buildCombinedExpr(
                        $ds,
                        $comparisonType,
                        $expression,
                        $this->buildNullValueExpr(
                            $ds,
                            $comparisonType,
                            $fieldName
                        )
                    );
                    break;
                }
            }
        }

        return $expression;
    }

    /**
     * @param integer|null $entityId
     * @param string $entityClassName
     * @return null|object
     */
    protected function getEntity($entityId, $entityClassName)
    {
        $entity = null;
        if ($entityId) {
            $entity = $this->registry
                ->getRepository($entityClassName)
                ->find($entityId);
        }

        return $entity;
    }

    /**
     * @param string $value
     * @param object $targetEntity
     * @param string $visibilityClass
     * @return bool
     */
    protected function isDefaultValue($targetEntity, $value, $visibilityClass)
    {
        /** @var string $defaultValue */
        $defaultValue = call_user_func([$visibilityClass, 'getDefault'], $targetEntity);

        return $defaultValue === $value;
    }
}
