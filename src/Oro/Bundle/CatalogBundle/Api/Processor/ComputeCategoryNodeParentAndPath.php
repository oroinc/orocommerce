<?php

namespace Oro\Bundle\CatalogBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\ComputeTreeNodePathField;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Component\ChainProcessor\ContextInterface;

/**
 * Computes a value of "parent" and "path" fields for CategoryNode entity.
 */
class ComputeCategoryNodeParentAndPath extends ComputeTreeNodePathField
{
    private const PARENT_FIELD = 'parent';

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getData();

        [$isParentFieldRequested, $isPathFieldRequested] = $this->checkRequestedFields($data, $context);
        if (!$isParentFieldRequested && !$isPathFieldRequested) {
            return;
        }

        $config = $context->getConfig();
        $nodeEntityClass = $this->getNodeEntityClass($context, $config);
        $nodeEntityIdFieldName = $this->doctrineHelper->getSingleEntityIdentifierFieldName($nodeEntityClass);
        $idFieldName = $context->getResultFieldName($nodeEntityIdFieldName);

        $parentNodes = $this->getParentNodes($data, $idFieldName);

        $nodes = $this->loadNodesData(
            $nodeEntityClass,
            $nodeEntityIdFieldName,
            $this->getNodeIds($parentNodes),
            $this->getTargetConfig($config, $isPathFieldRequested),
            $context->getNormalizationContext()
        );

        $context->setData(
            $this->updateData(
                $data,
                $idFieldName,
                $parentNodes,
                $nodes,
                $isParentFieldRequested,
                $isPathFieldRequested
            )
        );
    }

    /**
     * @return array [isParentFieldRequested, isPathFieldRequested]
     */
    private function checkRequestedFields(array $data, CustomizeLoadedDataContext $context): array
    {
        $isParentFieldRequested = false;
        $isPathFieldRequested = false;
        foreach ($data as $item) {
            if (!$isParentFieldRequested && $context->isFieldRequested(self::PARENT_FIELD, $item)) {
                $isParentFieldRequested = true;
            }
            if (!$isPathFieldRequested && $context->isFieldRequested($this->pathField, $item)) {
                $isPathFieldRequested = true;
            }
            if ($isParentFieldRequested && $isPathFieldRequested) {
                break;
            }
        }

        return [$isParentFieldRequested, $isPathFieldRequested];
    }

    private function getTargetConfig(
        EntityDefinitionConfig $config,
        bool $isPathFieldRequested
    ): EntityDefinitionConfig {
        if ($isPathFieldRequested) {
            return $config->getField($this->pathField)->getTargetEntity();
        }

        return $config->getField(self::PARENT_FIELD)->getTargetEntity();
    }

    /**
     * @param array  $data
     * @param string $idFieldName
     * @param array  $parentNodes [node id => [parent node id, ...], ...]
     * @param array  $nodes       [node id => node data, ...]
     * @param bool   $isParentFieldRequested
     * @param bool   $isPathFieldRequested
     *
     * @return array
     */
    private function updateData(
        array $data,
        string $idFieldName,
        array $parentNodes,
        array $nodes,
        bool $isParentFieldRequested,
        bool $isPathFieldRequested
    ): array {
        foreach ($data as $key => $item) {
            $parentNode = null;
            $pathNodes = [];
            $id = $item[$idFieldName];
            foreach ($parentNodes[$id] as $nodeId) {
                if (!empty($nodes[$nodeId])) {
                    if ($isParentFieldRequested) {
                        $parentNode = $nodes[$nodeId];
                    }
                    if ($isPathFieldRequested) {
                        $pathNodes[] = $nodes[$nodeId];
                    }
                }
            }
            if ($isParentFieldRequested) {
                $data[$key][self::PARENT_FIELD] = $parentNode;
            }
            if ($isPathFieldRequested) {
                $data[$key][$this->pathField] = $pathNodes;
            }
        }

        return $data;
    }
}
