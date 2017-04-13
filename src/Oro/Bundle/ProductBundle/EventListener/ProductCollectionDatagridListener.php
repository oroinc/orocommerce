<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SegmentBundle\Entity\Manager\SegmentManager;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Filter Product Collection datagrid by segment definition from request
 */
class ProductCollectionDatagridListener
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var SegmentManager
     */
    private $segmentManager;

    /**
     * @param RequestStack $requestStack
     * @param SegmentManager $segmentManager
     */
    public function __construct(RequestStack $requestStack, SegmentManager $segmentManager)
    {
        $this->requestStack = $requestStack;
        $this->segmentManager = $segmentManager;
    }

    /**
     * @param BuildAfter $event
     */
    public function onBuildAfter(BuildAfter $event)
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return;
        }

        $segmentDefinition = $request->get('segmentDefinition');

        $dataGrid = $event->getDatagrid();
        $dataSource = $dataGrid->getDatasource();

        if ($segmentDefinition && $dataSource instanceof OrmDatasource) {
            $dataGridQueryBuilder = $dataSource->getQueryBuilder();
            $this->addFilterBySegment($dataGridQueryBuilder, $segmentDefinition);
        }
    }

    /**
     * @param QueryBuilder $dataGridQueryBuilder
     * @param string $segmentDefinition
     */
    protected function addFilterBySegment(QueryBuilder $dataGridQueryBuilder, $segmentDefinition)
    {
        $productSegment = new Segment();
        $productSegment->setDefinition($segmentDefinition)
            ->setEntity(Product::class)
            ->setType(new SegmentType(SegmentType::TYPE_DYNAMIC));

        $this->segmentManager->filterBySegment($dataGridQueryBuilder, $productSegment);
    }
}
