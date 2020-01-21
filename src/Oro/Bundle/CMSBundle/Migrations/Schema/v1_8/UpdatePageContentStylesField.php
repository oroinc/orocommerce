<?php

namespace Oro\Bundle\CMSBundle\Migrations\Schema\v1_8;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Add "draft" config for cms page content_style
 */
class UpdatePageContentStylesField implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_cms_page');
        if ($table->hasColumn('content_style')) {
            $table->changeColumn(
                'content_style',
                [
                    OroOptions::KEY => [
                        ExtendOptionsManager::MODE_OPTION => ConfigModel::MODE_HIDDEN,
                        'extend' => ['is_extend' => true, 'owner' => ExtendScope::OWNER_SYSTEM],
                        'draft' => ['draftable' => true],
                    ]
                ]
            );
        }
    }
}
