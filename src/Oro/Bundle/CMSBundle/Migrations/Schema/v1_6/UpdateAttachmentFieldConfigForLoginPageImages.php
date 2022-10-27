<?php

namespace Oro\Bundle\CMSBundle\Migrations\Schema\v1_6;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\CMSBundle\Entity\LoginPage;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigFieldValueQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Update acl_protected entity field config option for image field of LoginPage entity.
 */
class UpdateAttachmentFieldConfigForLoginPageImages implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        foreach (['logoImage', 'backgroundImage'] as $fieldName) {
            $queries->addPostQuery(
                new UpdateEntityConfigFieldValueQuery(
                    LoginPage::class,
                    $fieldName,
                    'attachment',
                    'acl_protected',
                    false
                )
            );
        }
    }
}
