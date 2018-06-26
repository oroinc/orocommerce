<?php

namespace Oro\Bundle\WebCatalogBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigEntityValueQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\Form\Type\WebCatalogSelectType;

class UpdateWebcatalogFormType implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addPostQuery(
            new UpdateEntityConfigEntityValueQuery(
                WebCatalog::class,
                'form',
                'form_type',
                WebCatalogSelectType::class,
                'oro_web_catalog_select'
            )
        );
    }
}
