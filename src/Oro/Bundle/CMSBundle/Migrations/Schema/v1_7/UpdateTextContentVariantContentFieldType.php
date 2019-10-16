<?php

namespace Oro\Bundle\CMSBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGType;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Updates type for the field "content" of the TextContentVariant entity
 */
class UpdateTextContentVariantContentFieldType implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_cms_text_content_variant');
        if ($table->hasColumn('content')) {
            $contentType = $table->getColumn('content')->getType();
            if (!$contentType instanceof WYSIWYGType) {
                $table->changeColumn(
                    'content',
                    ['type' => WYSIWYGType::getType('wysiwyg'), 'comment' => '(DC2Type:wysiwyg)']
                );
                $table->addColumn('content_style', 'wysiwyg_style', ['notnull' => false]);
            }
        }
    }
}
