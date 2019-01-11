<?php

namespace Oro\Bundle\ConsentBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Replaces '"columnName":"acceptedConsents"' to '"columnName":"acceptedConsentsVirtual"'
 * Virtual name was changed from `acceptedConsents` (actually became real relation) to acceptedConsentsVirtual
 * So we need this migration to handle existing segments for CustomerUser entity with old `acceptedConsents`
 * field filter applied
 */
class UpdateAcceptedConsentsSegment implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $searchFor = '"columnName":"acceptedConsents"';
        $replaceOn = '"columnName":"acceptedConsentsVirtual"';
        $queries->addQuery("
            UPDATE oro_segment
            SET
                definition = replace(definition, '$searchFor', '$replaceOn')
            WHERE
                entity LIKE '%CustomerUser';
        ");
    }
}
