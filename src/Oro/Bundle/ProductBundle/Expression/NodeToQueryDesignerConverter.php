<?php

namespace Oro\Bundle\ProductBundle\Expression;

use Oro\Bundle\ProductBundle\Model\NodeExpressionQueryDesigner;
use Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner;
use Oro\Component\Expression\Node\NodeInterface;
use Oro\Component\Expression\ColumnInformationProviderInterface;

class NodeToQueryDesignerConverter
{
    /**
     * @var string
     */
    protected $entityClass;

    /**
     * @var ColumnInformationProviderInterface[]
     */
    protected $columnInformationProviders = [];

    /**
     * @param string $entityClass
     */
    public function __construct($entityClass)
    {
        $this->entityClass = $entityClass;
    }

    /**
     * @param ColumnInformationProviderInterface $provider
     */
    public function addColumnInformationProvider(ColumnInformationProviderInterface $provider)
    {
        array_unshift($this->columnInformationProviders, $provider);
    }

    /**
     * @param NodeInterface $node
     * @return AbstractQueryDesigner
     */
    public function convert(NodeInterface $node)
    {
        $source = $this->createQueryDesigner();
        $definition = $this->getDefinitionByNode($node);
        $source->setDefinition(json_encode($definition));

        return $source;
    }

    /**
     * @return NodeExpressionQueryDesigner
     */
    protected function createQueryDesigner()
    {
        $source = new NodeExpressionQueryDesigner();
        $source->setEntity($this->entityClass);

        return $source;
    }

    /**
     * @param NodeInterface $node
     * @return array
     */
    protected function getDefinitionByNode(NodeInterface $node)
    {
        $definition = [
            'columns' => [],
        ];
        $addedColumns = [];
        foreach ($node->getNodes() as $subNode) {
            foreach ($this->columnInformationProviders as $provider) {
                if ($provider->fillColumnInformation($subNode, $addedColumns, $definition)) {
                    break;
                }
            }
        }

        return $definition;
    }
}
