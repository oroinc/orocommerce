<?php

namespace OroB2B\Bundle\SaleBundle\Migrations\Schema\v1_9;

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
        // email to quote association
        $this->renameExtension->renameTable(
            $schema,
            $queries,
            'oro_rel_26535370aab0e4f0a0472d',
            'oro_rel_26535370aab0e4f0b5ec88'
        );

        // calendar event to quote association
        $this->renameExtension->renameTable(
            $schema,
            $queries,
            'oro_rel_46a29d19aab0e4f0a0472d',
            'oro_rel_46a29d19aab0e4f0b5ec88'
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
