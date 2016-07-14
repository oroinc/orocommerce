<?php

namespace OroB2B\Bundle\PricingBundle\Expression;

use Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner;

use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\PricingBundle\Model\PriceListQueryDesigner;
use OroB2B\Bundle\PricingBundle\Provider\PriceRuleFieldsProvider;
use OroB2B\Bundle\ProductBundle\Entity\Product;

class NodeToQueryDesignerConverter
{
    /**
     * @var PriceRuleFieldsProvider
     */
    protected $attributeProvider;

    /**
     * @param PriceRuleFieldsProvider $attributeProvider
     */
    public function __construct(PriceRuleFieldsProvider $attributeProvider)
    {
        $this->attributeProvider = $attributeProvider;
    }

    /**
     * @param NodeInterface $node
     * @return AbstractQueryDesigner
     */
    public function convert(NodeInterface $node)
    {
        $source = new PriceListQueryDesigner();
        $source->setEntity(Product::class);

        $definition = [
            'columns' => [],
        ];
        $addedColumns = [];
        foreach ($node->getNodes() as $subNode) {
            if ($subNode instanceof NameNode) {
                $this->convertNames($subNode, $addedColumns, $definition);
            } elseif ($subNode instanceof RelationNode) {
                $this->convertRelations($subNode, $addedColumns, $definition);
            }
        }

        $source->setDefinition(json_encode($definition));

        return $source;
    }

    /**
     * @param NameNode $subNode
     * @param array $addedColumns
     * @param array $definition
     */
    protected function convertNames(NameNode $subNode, array &$addedColumns, array &$definition)
    {
        if ($subNode->getContainer() === Product::class) {
            if (empty($addedColumns[$subNode->getField()])) {
                $definition['columns'][] = [
                    'name' => $subNode->getField(),
                    'table_identifier' => $subNode->getContainer(),
                ];
                $addedColumns[$subNode->getField()] = true;
            }
        } elseif ($subNode->getContainer() === Category::class) {
            $field = $subNode->getField() ?: 'id';
            $path = sprintf('%1$s::products+%1$s::%2$s', Category::class, $field);
            if (empty($addedColumns[$path])) {
                $definition['columns'][] = [
                    'name' => $path,
                    'table_identifier' => $subNode->getContainer(),
                ];
                $addedColumns[$path] = true;
            }
        } elseif ($subNode->getContainer() === ProductPrice::class) {
            $path = sprintf('%1$s::product+%1$s::%2$s', ProductPrice::class, $subNode->getField());
            if (empty($addedColumns[$path])) {
                $definition['columns'][] = [
                    'name' => $path,
                    'table_identifier' => $subNode->getContainer(),
                ];
                $addedColumns[$path] = true;
            }
        } else {
            throw new \InvalidArgumentException(
                sprintf('Unsupported field %s::%s', $subNode->getContainer(), $subNode->getField())
            );
        }
    }

    /**
     * @param RelationNode $subNode
     * @param array $addedColumns
     * @param array $definition
     */
    protected function convertRelations(RelationNode $subNode, array &$addedColumns, array &$definition)
    {
        $tableIdentifier = $subNode->getRelationAlias();

        $resolvedContainer = $this->attributeProvider->getRealClassName($tableIdentifier);
        if ($resolvedContainer === PriceAttributeProductPrice::class) {
            $path = sprintf(
                '%s+%s::%s',
                $subNode->getField(),
                PriceAttributeProductPrice::class,
                $subNode->getRelationField()
            );
            if (empty($addedColumns[$path])) {
                $definition['columns'][] = [
                    'name' => $path,
                    'table_identifier' => $tableIdentifier,
                ];
                $addedColumns[$path] = true;
            }
        } else {
            throw new \InvalidArgumentException(
                sprintf(
                    'Unsupported field %s::%s::%s',
                    $subNode->getContainer(),
                    $subNode->getField(),
                    $subNode->getRelationField()
                )
            );
        }
    }
}
