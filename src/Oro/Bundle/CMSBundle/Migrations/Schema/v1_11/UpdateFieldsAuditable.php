<?php

namespace Oro\Bundle\CMSBundle\Migrations\Schema\v1_11;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigFieldValueQuery;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Make wysiwyg field of Localized Fallback Value be auditable
 */
class UpdateFieldsAuditable implements Migration
{
    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ShortMethodName)
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->updateWysiwygFieldAuditable($queries);
        $this->updatePageContentNotAuditable($queries);
    }

    private function updateWysiwygFieldAuditable(QueryBag $queries): void
    {
        $queries->addPostQuery(
            new UpdateEntityConfigFieldValueQuery(
                LocalizedFallbackValue::class,
                'wysiwyg',
                'dataaudit',
                'auditable',
                true
            )
        );
    }

    private function updatePageContentNotAuditable(QueryBag $queries): void
    {
        $queries->addPostQuery(
            new UpdateEntityConfigFieldValueQuery(
                Page::class,
                'content',
                'dataaudit',
                'auditable',
                false
            )
        );
    }
}
