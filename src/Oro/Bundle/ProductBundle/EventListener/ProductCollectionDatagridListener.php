<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\NameStrategyInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Service\ProductCollectionDefinitionConverter;
use Oro\Bundle\SegmentBundle\Entity\Manager\SegmentManager;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Filter Product Collection datagrid by segment definition from request
 */
class ProductCollectionDatagridListener
{
    const SEGMENT_DEFINITION_PARAMETER_KEY = 'sd_';
    const DEFINITION_KEY = 'definition';
    const INCLUDED_KEY = 'included';
    const EXCLUDED_KEY = 'excluded';

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var SegmentManager
     */
    private $segmentManager;

    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var NameStrategyInterface
     */
    private $nameStrategy;

    /**
     * @var ProductCollectionDefinitionConverter
     */
    private $definitionConverter;

    public function __construct(
        RequestStack $requestStack,
        SegmentManager $segmentManager,
        ManagerRegistry $registry,
        NameStrategyInterface $nameStrategy,
        ProductCollectionDefinitionConverter $definitionConverter
    ) {
        $this->requestStack = $requestStack;
        $this->segmentManager = $segmentManager;
        $this->registry = $registry;
        $this->nameStrategy = $nameStrategy;
        $this->definitionConverter = $definitionConverter;
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

        $definition = json_decode($segmentData[self::DEFINITION_KEY], true);
        if (!$this->definitionConverter->hasFilters($definition) && !$segmentData[self::INCLUDED_KEY]) {
            $dataGridQueryBuilder->andWhere('1 = 0');
            return;
        }

        $segmentDefinition = $this->getSegmentDefinition($segmentData);
        if ($segmentDefinition) {
            $this->addFilterBySegment($dataGridQueryBuilder, $segmentDefinition);
        }
    }

    /**
     * @param QueryBuilder $dataGridQueryBuilder
     * @param string $segmentDefinition
     */
    private function addFilterBySegment(QueryBuilder $dataGridQueryBuilder, $segmentDefinition)
    {
        /** @var EntityManager $em */
        $em = $this->registry->getManagerForClass(SegmentType::class);
        $dynamicSegmentType = $em->getReference(SegmentType::class, SegmentType::TYPE_DYNAMIC);

        $productSegment = new Segment();
        $productSegment->setDefinition($segmentDefinition)
            ->setEntity(Product::class)
            ->setType($dynamicSegmentType);

        $this->segmentManager->filterBySegment($dataGridQueryBuilder, $productSegment);
    }

    /**
     * @param array $segmentData
     * @return string
     */
    private function getSegmentDefinition(array $segmentData): string
    {
        return $this->definitionConverter->putConditionsInDefinition(
            $segmentData[self::DEFINITION_KEY],
            $segmentData[self::EXCLUDED_KEY],
            $segmentData[self::INCLUDED_KEY]
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
        if (isset($params['segmentDefinition'])) {
            return [
                self::DEFINITION_KEY => $params['segmentDefinition'],
                self::INCLUDED_KEY => $params['includedProducts'] ?? null,
                self::EXCLUDED_KEY => $params['excludedProducts'] ?? null
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
            self::DEFINITION_KEY => $request->get($dataParameterName),
            self::INCLUDED_KEY => $request->get($dataParameterName . ':incl'),
            self::EXCLUDED_KEY => $request->get($dataParameterName . ':excl'),
        ];
    }
}
