<?php

namespace Oro\Bundle\PricingBundle\Expression;

use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Model\PriceListQueryDesigner;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner;
use Oro\Component\Expression\FieldsProviderInterface;
use Oro\Component\Expression\Node;

class NodeToQueryDesignerConverter
{
    /**
     * @var FieldsProviderInterface
     */
    protected $fieldsProvider;

    /**
     * @param FieldsProviderInterface $fieldsProvider
     */
    public function __construct(FieldsProviderInterface $fieldsProvider)
    {
        $this->fieldsProvider = $fieldsProvider;
    }

    /**
     * @param Node\NodeInterface $node
     * @return AbstractQueryDesigner
     */
    public function convert(Node\NodeInterface $node)
    {
        $source = new PriceListQueryDesigner();
        $source->setEntity(Product::class);

        $definition = [
            'columns' => [],
        ];
        $addedColumns = [];
        foreach ($node->getNodes() as $subNode) {
            if ($subNode instanceof Node\NameNode) {
                $this->convertNames($subNode, $addedColumns, $definition);
            } elseif ($subNode instanceof Node\RelationNode) {
                $this->convertRelations($subNode, $addedColumns, $definition);
            }
        }

        $source->setDefinition(json_encode($definition));

        return $source;
    }

    /**
     * @param Node\NameNode $subNode
     * @param array $addedColumns
     * @param array $definition
     */
    protected function convertNames(Node\NameNode $subNode, array &$addedColumns, array &$definition)
    {
        if ($subNode->getContainer() === Product::class) {
            if (empty($addedColumns[$subNode->getField()])) {
                $definition['columns'][] = [
                    'name' => $subNode->getField(),
                    'table_identifier' => $subNode->getContainer(),
                ];
                $addedColumns[$subNode->getField()] = true;
            }
        } elseif ($subNode->getContainer() === PriceList::class) {
            $priceListKey = 'pricelist|' . $subNode->getContainerId();
            if (empty($addedColumns[$priceListKey])) {
                $definition['price_lists'][] = $subNode->getContainerId();
                $addedColumns[$priceListKey] = true;
            }
        } else {
            throw new \InvalidArgumentException(
                sprintf('Unsupported field %s::%s', $subNode->getContainer(), $subNode->getField())
            );
        }
    }

    /**
     * @param Node\RelationNode $subNode
     * @param array $addedColumns
     * @param array $definition
     */
    protected function convertRelations(Node\RelationNode $subNode, array &$addedColumns, array &$definition)
    {
        $tableIdentifier = $subNode->getRelationAlias();

        if ($subNode->getContainer() === PriceList::class && $subNode->getField() === 'prices') {
            $pricesKey = 'price|' . $subNode->getContainerId();
            if (empty($addedColumns[$pricesKey])) {
                $definition['prices'][] = $subNode->getContainerId();
                $addedColumns[$pricesKey] = true;
            }
        } else {
            $resolvedContainer = $this->fieldsProvider->getRealClassName($tableIdentifier);
            $path = sprintf(
                '%s+%s::%s',
                $subNode->getField(),
                $resolvedContainer,
                $subNode->getRelationField()
            );
            if (empty($addedColumns[$path])) {
                $definition['columns'][] = [
                    'name' => $path,
                    'table_identifier' => $tableIdentifier,
                ];
                $addedColumns[$path] = true;
            }
        }
    }
}
