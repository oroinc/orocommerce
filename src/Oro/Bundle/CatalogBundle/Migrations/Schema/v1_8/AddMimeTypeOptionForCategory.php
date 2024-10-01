<?php

namespace Oro\Bundle\CatalogBundle\Migrations\Schema\v1_8;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\AttachmentBundle\Migration\SetAllowedMimeTypesForImageFieldQuery;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddMimeTypeOptionForCategory implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $queries->addQuery(new SetAllowedMimeTypesForImageFieldQuery(
            Category::class,
            'smallImage',
            ['image/gif', 'image/jpeg', 'image/png', 'image/svg+xml']
        ));
        $queries->addQuery(new SetAllowedMimeTypesForImageFieldQuery(
            Category::class,
            'largeImage',
            ['image/gif', 'image/jpeg', 'image/png', 'image/svg+xml']
        ));
    }
}
