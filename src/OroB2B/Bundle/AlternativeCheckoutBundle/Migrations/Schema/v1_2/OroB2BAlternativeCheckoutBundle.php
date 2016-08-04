<?php

namespace OroB2B\Bundle\AlternativeCheckoutBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

use OroB2B\Bundle\AlternativeCheckoutBundle\Migrations\Schema\v1_2\AlternativeCheckout\MoveCheckoutAddressDataQuery;
use OroB2B\Bundle\AlternativeCheckoutBundle\Migrations\Schema\v1_2\AlternativeCheckout\UpdateCheckoutWorkflowDataQuery;

class OroB2BAlternativeCheckoutBundle implements Migration, OrderedMigrationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 20;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addPreQuery(new UpdateCheckoutWorkflowDataQuery());
        $queries->addPreQuery(new MoveCheckoutAddressDataQuery());

        $this->removeAlternativeCheckoutTable($schema);

        $queries->addPreQuery(
            new ParametrizedSqlMigrationQuery(
                'DELETE FROM oro_entity_config WHERE class_name = :class_name',
                [
                    'class_name'  => 'OroB2B\Bundle\AlternativeCheckoutBundle\Entity\AlternativeCheckout',
                ],
                [
                    'class_name'  => Type::STRING
                ]
            )
        );
    }

    /**
     * @param Schema $schema
     */
    protected function removeAlternativeCheckoutTable(Schema $schema)
    {
        $schema->dropTable('orob2b_alternative_checkout');
    }
}
