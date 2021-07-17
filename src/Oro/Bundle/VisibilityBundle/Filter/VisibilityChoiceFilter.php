<?php

namespace Oro\Bundle\VisibilityBundle\Filter;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Filter\ChoiceFilter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Symfony\Component\Form\FormFactoryInterface;

/**
 * The filter by product visibility.
 */
class VisibilityChoiceFilter extends ChoiceFilter
{
    /** @var ManagerRegistry */
    protected $doctrine;

    public function __construct(FormFactoryInterface $factory, FilterUtility $util, ManagerRegistry $doctrine)
    {
        parent::__construct($factory, $util);
        $this->doctrine = $doctrine;
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
                throw new \InvalidArgumentException('Required filter parameters missing');
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
     * @param int|null $entityId
     * @param string   $entityClassName
     *
     * @return object|null
     */
    protected function getEntity($entityId, $entityClassName)
    {
        $entity = null;
        if ($entityId) {
            $entity = $this->doctrine->getRepository($entityClassName)->find($entityId);
        }

        return $entity;
    }

    /**
     * @param string $value
     * @param object $targetEntity
     * @param string $visibilityClass
     *
     * @return bool
     */
    protected function isDefaultValue($targetEntity, $value, $visibilityClass)
    {
        /** @var string $defaultValue */
        $defaultValue = call_user_func([$visibilityClass, 'getDefault'], $targetEntity);

        return $defaultValue === $value;
    }
}
