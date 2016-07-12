<?php

namespace OroB2B\Bundle\ProductBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\Update\UpdateContext;
use Oro\Bundle\ApiBundle\Util\CriteriaConnector;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class BuildSingleProductQuery implements ProcessorInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var CriteriaConnector */
    protected $criteriaConnector;

    /**
     * @param DoctrineHelper    $doctrineHelper
     * @param CriteriaConnector $criteriaConnector
     */
    public function __construct(DoctrineHelper $doctrineHelper, CriteriaConnector $criteriaConnector)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->criteriaConnector = $criteriaConnector;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var UpdateContext $context */

        if ($context->hasQuery()) {
            // a query is already built
            return;
        }

        $criteria = $context->getCriteria();
        if (null === $criteria) {
            // the criteria object does not exist
            return;
        }

        $entityClass = $context->getClassName();
        if (!$this->doctrineHelper->isManageableEntityClass($entityClass)) {
            // only manageable entities are supported
            return;
        }

        $queryBuilder = $this->doctrineHelper->getEntityRepositoryForClass($entityClass)->createQueryBuilder('e');
        $this->criteriaConnector->applyCriteria($queryBuilder, $criteria);

        $requestData = $context->getRequestData();
        $sku = $requestData['sku'];
        $queryBuilder
            ->andWhere($queryBuilder->expr()->eq('e.sku', ':sku'))
            ->setParameter('sku', $sku);

        $context->setQuery($queryBuilder);
    }
}
