<?php

namespace Oro\Bundle\ProductBundle\Expression\ColumnInformation;

use Oro\Component\Expression\FieldsProviderInterface;
use Oro\Component\Expression\Node\NameNode;
use Oro\Component\Expression\Node\NodeInterface;
use Oro\Component\Expression\Node\RelationNode;
use Oro\Component\Expression\ColumnInformationProviderInterface;

class DefaultProvider implements ColumnInformationProviderInterface
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
     * {@inheritdoc}
     */
    public function fillColumnInformation(NodeInterface $node, array &$addedColumns, array &$definition)
    {
        if ($node instanceof NameNode) {
            return $this->convertNameNode($node, $addedColumns, $definition);
        } elseif ($node instanceof RelationNode) {
            return $this->convertRelationNode($node, $addedColumns, $definition);
        }

        return false;
    }

    /**
     * @param NameNode $node
     * @param array $addedColumns
     * @param array $definition
     * @return bool
     */
    protected function convertNameNode(NameNode $node, array &$addedColumns, array &$definition)
    {
        if (empty($addedColumns[$node->getField()])) {
            $definition['columns'][] = [
                'name' => $node->getField(),
                'table_identifier' => $node->getContainer(),
            ];
            $addedColumns[$node->getField()] = true;
        }

        return true;
    }

    /**
     * @param RelationNode $node
     * @param array $addedColumns
     * @param array $definition
     * @return bool
     */
    protected function convertRelationNode(RelationNode $node, array &$addedColumns, array &$definition)
    {
        $tableIdentifier = $node->getRelationAlias();

        $resolvedContainer = $this->fieldsProvider->getRealClassName($tableIdentifier);
        $path = sprintf(
            '%s+%s::%s',
            $node->getField(),
            $resolvedContainer,
            $node->getRelationField()
        );
        if (empty($addedColumns[$path])) {
            $definition['columns'][] = [
                'name' => $path,
                'table_identifier' => $tableIdentifier,
            ];
            $addedColumns[$path] = true;
        }

        return true;
    }
}
