<?php

declare(strict_types=1);

namespace Oro\Bundle\CommerceBundle\Migrations\Schema\v7_1_0_0;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Removes the integration data of the removed Apruve payment integration.
 */
class RemoveApruveIntegration implements Migration
{
    /** Value of the "type" column of the "oro_integration_channel" table. */
    private const CHANNEL_TYPE = 'apruve';

    /**
     * Value of the "type" discriminator column of the "oro_integration_transport" table.
     * ApruveSettings did not declare a discriminator map, so Doctrine used the lowercased short class name.
     */
    private const TRANSPORT_TYPE = 'apruvesettings';

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        if (class_exists('Oro\Bundle\ApruveBundle\OroApruveBundle', false)) {
            return;
        }

        // Rows of "oro_integration_channel_status" are deleted explicitly:
        // they are covered by an "ON DELETE CASCADE" rule, but deleting them first keeps this migration working
        // even on a database where that foreign key was altered.
        //
        // Rows of "oro_integration_transport" must be deleted manually because "oro_integration_channel"
        // owns the relation and there is no cascade in this direction.
        //
        // Rows of "oro_apruve_short_label" and "oro_apruve_trans_label" are deleted automatically
        // because these tables have "ON DELETE CASCADE" rules for "transport_id".

        $queries->addQuery(new ParametrizedSqlMigrationQuery(
            'DELETE FROM oro_integration_channel_status WHERE EXISTS('
            . 'SELECT 1 FROM oro_integration_channel WHERE'
            . ' oro_integration_channel.type = :channel_type'
            . ' AND oro_integration_channel_status.channel_id = oro_integration_channel.id)',
            ['channel_type' => self::CHANNEL_TYPE],
            ['channel_type' => Types::STRING]
        ));
        $queries->addQuery(new ParametrizedSqlMigrationQuery(
            'DELETE FROM oro_integration_channel WHERE type = :channel_type',
            ['channel_type' => self::CHANNEL_TYPE],
            ['channel_type' => Types::STRING]
        ));
        $queries->addQuery(new ParametrizedSqlMigrationQuery(
            'DELETE FROM oro_integration_transport WHERE type = :transport_type',
            ['transport_type' => self::TRANSPORT_TYPE],
            ['transport_type' => Types::STRING]
        ));
    }
}
