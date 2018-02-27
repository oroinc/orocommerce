<?php

namespace Oro\Bundle\CatalogBundle\Migrations\Schema\v1_8;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\AttachmentBundle\Migration\SetAllowedMimeTypesForImageFieldQuery;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Migrations\Schema\OroCatalogBundleInstaller;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddMimeTypeOptionForCategory implements Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addQuery(new SetAllowedMimeTypesForImageFieldQuery(
            Category::class,
            'smallImage',
            OroCatalogBundleInstaller::MIME_TYPES
        ));
        $queries->addQuery(new SetAllowedMimeTypesForImageFieldQuery(
            Category::class,
            'largeImage',
            OroCatalogBundleInstaller::MIME_TYPES
        ));
    }
}
