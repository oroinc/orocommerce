<?php

namespace OroB2B\Bundle\OrderBundle\Migrations\Schema\v1_5;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class RenameTablesAndColumns implements Migration, RenameExtensionAwareInterface
{
    /**
     * @var RenameExtension
     */
    protected $renameExtension;

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        // email to order association
        $this->renameExtension->renameTable(
            $schema,
            $queries,
            'oro_rel_2653537034e8bc9c23a92e',
            'oro_rel_2653537034e8bc9c2ddbe0'
        );

        // calendar event to order association
        $this->renameExtension->renameTable(
            $schema,
            $queries,
            'oro_rel_46a29d1934e8bc9c23a92e',
            'oro_rel_46a29d1934e8bc9c2ddbe0'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setRenameExtension(RenameExtension $renameExtension)
    {
        $this->renameExtension = $renameExtension;
    }
}
