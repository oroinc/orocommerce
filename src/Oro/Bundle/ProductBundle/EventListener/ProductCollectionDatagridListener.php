<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
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
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @param RequestStack $requestStack
     * @param SegmentManager $segmentManager
     * @param ManagerRegistry $registry
     */
    public function __construct(RequestStack $requestStack, SegmentManager $segmentManager, ManagerRegistry $registry)
    {
        $this->requestStack = $requestStack;
        $this->segmentManager = $segmentManager;
        $this->registry = $registry;
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
        /** @var EntityManager $em */
        $em = $this->registry->getManagerForClass(SegmentType::class);
        $dynamicSegmentType = $em->getReference(SegmentType::class, SegmentType::TYPE_DYNAMIC);

        $productSegment = new Segment();
        $productSegment->setDefinition($segmentDefinition)
            ->setEntity(Product::class)
            ->setType($dynamicSegmentType);

        $this->segmentManager->filterBySegment($dataGridQueryBuilder, $productSegment);
    }
}
