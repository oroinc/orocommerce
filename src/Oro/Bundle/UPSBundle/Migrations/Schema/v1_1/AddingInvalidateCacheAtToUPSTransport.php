<?php
namespace Oro\Bundle\UPSBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddingInvalidateCacheAtToUPSTransport implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_integration_transport');
        $table->addColumn(
            'ups_invalidate_cache_at',
            'datetime',
            ['notnull' => false, 'comment' => '(DC2Type:datetime)']
        );
    }
}
