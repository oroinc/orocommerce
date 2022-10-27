<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\NameStrategyInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\ProductBundle\Entity\CollectionSortOrder;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Join Product Collection Sort Order to datagrid by segment definition from request
 */
class ProductCollectionContentVariantDatagridListener
{
    public const SEGMENT_DEFINITION_PARAMETER_KEY = 'sd_';
    public const SEGMENT_ID_PARAMETER_KEY = 'si_';
    public const DEFINITION_KEY = 'definition';
    public const ID_KEY = 'id';

    public function __construct(
        protected RequestStack $requestStack,
        protected NameStrategyInterface $nameStrategy
    ) {
    }

    public function onBuildBefore(BuildBefore $event)
    {
        $this->buildCellSelectionSelector($event->getDatagrid());
    }

    public function onBuildAfter(BuildAfter $event)
    {
        $dataGrid = $event->getDatagrid();

        $segmentData = $this->getSegmentData($dataGrid);
        if (!$segmentData) {
            return;
        }

        $dataSource = $dataGrid->getDatasource();
        if (!$dataSource instanceof OrmDatasource) {
            return;
        }

        $dataGridQueryBuilder = $dataSource->getQueryBuilder();

        if (!is_null($segmentData[self::ID_KEY]) && !empty($segmentData[self::ID_KEY])) {
            $this->joinProductCollectionSortOrder($dataGridQueryBuilder, (int)$segmentData[self::ID_KEY]);
        } else {
            $this->joinProductCollectionSortOrder($dataGridQueryBuilder, 0);
        }
    }

    /**
     * @param QueryBuilder$queryBuilder
     * @param int|null $segmentId
     * @return void
     */
    private function joinProductCollectionSortOrder(QueryBuilder $queryBuilder, ?int $segmentId): void
    {
        $expr = $queryBuilder->expr();
        $queryBuilder
            ->addSelect('collectionSortOrder.sortOrder as categorySortOrder');

        $joinCollectionSortOrdersExpr = $expr->andX()
            ->add(
                $expr->eq(
                    'collectionSortOrder.product',
                    'product.id'
                )
            );

        $joinCollectionSortOrdersExpr->add('IDENTITY(collectionSortOrder.segment) =:segmentId');

        $queryBuilder->leftJoin(
            CollectionSortOrder::class,
            'collectionSortOrder',
            Join::WITH,
            $joinCollectionSortOrdersExpr
        );
        $queryBuilder->setParameter('segmentId', $segmentId);
    }

    /**
     * @param DatagridInterface $datagrid
     * @return void
     */
    public function buildCellSelectionSelector(DatagridInterface $datagrid): void
    {
        $datagrid->getConfig()->offsetSetByPath(
            '[options][cellSelection][selector]',
            sprintf(
                '#%s--%s',
                'categorySortOrder',
                str_replace(
                    ':',
                    '__',
                    $this->nameStrategy->buildGridFullName($datagrid->getName(), $datagrid->getScope())
                )
            )
        );
    }

    /**
     * @param DatagridInterface $dataGrid
     * @return array
     */
    private function getSegmentData(DatagridInterface $dataGrid): array
    {
        $parameters = $this->getSegmentDataFromGridParameters($dataGrid);
        if (!$parameters) {
            $parameters = $this->getSegmentDataFromRequest($dataGrid);
        }

        return $parameters;
    }

    /**
     * @param DatagridInterface $dataGrid
     * @return array|null
     */
    private function getSegmentDataFromGridParameters(DatagridInterface $dataGrid): ?array
    {
        $parameters = $dataGrid->getParameters();

        $params = $parameters->get('params', []);
        if (isset($params['segmentId']) && isset($params['segmentDefinition'])) {
            return [
                self::ID_KEY => $params['segmentId'],
                self::DEFINITION_KEY => $params['segmentDefinition']
            ];
        }

        return null;
    }

    /**
     * @param DatagridInterface $dataGrid
     * @return array
     */
    private function getSegmentDataFromRequest(DatagridInterface $dataGrid): array
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return [];
        }

        $scope = $dataGrid->getScope();
        $gridFullName = $this->nameStrategy->buildGridFullName($dataGrid->getName(), $scope);
        $dataParameterName = self::SEGMENT_DEFINITION_PARAMETER_KEY . $gridFullName;

        return [
            self::ID_KEY => $request->get(self::SEGMENT_ID_PARAMETER_KEY . $gridFullName),
            self::DEFINITION_KEY => $request->get($dataParameterName)
        ];
    }
}
