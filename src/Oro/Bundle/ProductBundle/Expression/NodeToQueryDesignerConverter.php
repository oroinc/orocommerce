<?php

namespace Oro\Bundle\ProductBundle\Expression;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner;
use Oro\Bundle\QueryDesignerBundle\Model\QueryDesigner;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\QueryDefinitionUtil;
use Oro\Component\Expression\ColumnInformationProviderInterface;
use Oro\Component\Expression\Node\NodeInterface;

/**
 * Converts configured query expressions for Product entity to a query designer object.
 */
class NodeToQueryDesignerConverter
{
    /** @var ColumnInformationProviderInterface[] */
    private $columnInformationProviders = [];

    /**
     * @param ColumnInformationProviderInterface $provider
     */
    public function addColumnInformationProvider(ColumnInformationProviderInterface $provider): void
    {
        array_unshift($this->columnInformationProviders, $provider);
    }

    /**
     * @param NodeInterface $node
     *
     * @return AbstractQueryDesigner
     */
    public function convert(NodeInterface $node): AbstractQueryDesigner
    {
        return new QueryDesigner(
            Product::class,
            QueryDefinitionUtil::encodeDefinition($this->getDefinitionByNode($node))
        );
    }

    /**
     * @param NodeInterface $node
     *
     * @return array
     */
    private function getDefinitionByNode(NodeInterface $node): array
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
