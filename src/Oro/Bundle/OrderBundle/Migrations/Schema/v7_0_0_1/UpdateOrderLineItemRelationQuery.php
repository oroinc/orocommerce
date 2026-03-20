<?php

namespace Oro\Bundle\OrderBundle\Migrations\Schema\v7_0_0_1;

use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Psr\Log\LoggerInterface;

/**
 * Updates OrderLineItem relations to Order in report definitions to use the new field named orders.
 */
class UpdateOrderLineItemRelationQuery extends ParametrizedSqlMigrationQuery
{
    #[\Override]
    public function getDescription()
    {
        return 'Fixes OrderLineItem relations to Order in report definitions';
    }

    #[\Override]
    public function execute(LoggerInterface $logger)
    {
        $entityClassName = OrderLineItem::class;
        $definitionEntittyName = str_replace('\\', '\\\\\\\\', $entityClassName);
        $searchDefinition = sprintf('%s::order+', $definitionEntittyName);
        $replaceDefinition = sprintf('%s::orders+', $definitionEntittyName);

        $this->addSql(
            'UPDATE oro_report SET definition = REPLACE(definition, :search, :replace) WHERE entity = :entity',
            [
                'search' => 'order+',
                'replace' => 'orders+',
                'entity' => $entityClassName
            ],
            [
                'search' => Types::STRING,
                'replace' => Types::STRING,
                'entity' => Types::STRING,
            ]
        );

        $this->addSql(
            'UPDATE oro_report SET definition = REPLACE(definition, :search, :replace) WHERE definition LIKE :pattern',
            [
                'search' => $searchDefinition,
                'replace' => $replaceDefinition,
                'pattern' => "%{$searchDefinition}%"
            ],
            [
                'search' => Types::STRING,
                'replace' => Types::STRING,
                'pattern' => Types::STRING
            ]
        );

        parent::execute($logger);
    }
}
