<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine\AsyncMessaging;

use Doctrine\ORM\AbstractQuery;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WebsiteSearchBundle\Engine\Context\ContextTrait;

class ReindexMessageGranularizer
{
    use ContextTrait;

    /**
     * The maximum number of IDs per one reindex request message
     */
    const ID_CHUNK_SIZE = 100;

    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param array|string $entities
     * @param array $websites
     * @param array $context
     * @return array
     */
    public function process($entities, array $websites, array $context)
    {
        $entities = (array)$entities;

        $entityIds = $this->getContextEntityIds($context);

        if (empty($entityIds)) {
            $entityIds = $this->getEntityIds($entities);
        } else {
            $entityIds = [current($entities) => $entityIds]; // always have an array here
        }

        return $this->granulate($entities, $websites, $entityIds);
    }

    /**
     * @param array $entities
     * @return array
     */
    private function getEntityIds($entities)
    {
        $entities = (array)$entities;

        $result = [];

        foreach ($entities as $entityName) {
            $entityRepository = $this->doctrineHelper->getEntityManager($entityName);
            $queryBuilder     = $entityRepository->createQueryBuilder('entity');
            $identifierName   = $this->doctrineHelper->getSingleEntityIdentifierFieldName($entityName);
            $queryBuilder
                ->select("entity.$identifierName as id")
                ->from($entityName, 'entity');

            $data = $queryBuilder->getQuery()
                ->getResult(AbstractQuery::HYDRATE_ARRAY);

            $result[$entityName] = !empty($data) && is_array($data) && isset($data[0]['id']) ?
                array_column($data, 'id') : $data;
        }

        return $result;
    }

    /**
     * @param array $entities
     * @param array $websites
     * @param array $entityIds
     * @return array
     */
    private function granulate(array $entities, array $websites, array $entityIds)
    {
        $result = [];

        if (count($entities) === 1 && count($entityIds[current($entities)]) <= self::ID_CHUNK_SIZE) {
            $entity      = current($entities);
            $itemContext = [];
            $itemContext = $this->setContextEntityIds($itemContext, $entityIds[$entity]);
            $itemContext = $this->setContextWebsiteIds($itemContext, $websites);

            return [
                [
                    'class'   => [$entity],
                    'context' => $itemContext
                ]
            ];
        }

        if (empty($websites)) {
            foreach ($entities as $entity) {
                $chunks = $this->getChunksOfIds($entityIds[$entity]);
                foreach ($chunks as $chunk) {
                    $itemContext = [];
                    $itemContext = $this->setContextEntityIds($itemContext, $chunk);
                    $result[]    = [
                        'class'   => [$entity],
                        'context' => $itemContext
                    ];
                }
            }

            return $result;
        }

        foreach ($websites as $website) {
            foreach ($entities as $entity) {
                $chunks = $this->getChunksOfIds($entityIds[$entity]);
                foreach ($chunks as $chunk) {
                    $itemContext = [];
                    $itemContext = $this->setContextEntityIds($itemContext, $chunk);
                    $itemContext = $this->setContextWebsiteIds($itemContext, [$website]);
                    $result[]    = [
                        'class'   => [$entity],
                        'context' => $itemContext
                    ];
                }
            }
        }

        return $result;
    }

    /**
     * @param array $ids
     * @return array
     */
    private function getChunksOfIds($ids)
    {
        return array_chunk($ids, self::ID_CHUNK_SIZE);
    }
}
