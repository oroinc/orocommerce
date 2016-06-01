<?php

namespace OroB2B\Bundle\FallbackBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BFallbackBundle implements Migration, RenameExtensionAwareInterface
{
    /**
     * @var RenameExtension
     */
    protected $renameExtension;

    /**
     * @inheritdoc
     */
    public function setRenameExtension(RenameExtension $renameExtension)
    {
        $this->renameExtension = $renameExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('orob2b_fallback_locale_value');
        $table->renameIndex('idx_orob2b_fallback_fallback', 'idx_fallback');
        $table->renameIndex('idx_orob2b_fallback_string', 'idx_string');

        $this->renameExtension->renameTable(
            $schema,
            $queries,
            'orob2b_fallback_locale_value',
            'oro_fallback_locale_value'
        );
    }
}
