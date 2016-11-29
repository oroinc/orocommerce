<?php

namespace Oro\Bundle\CatalogBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\FrontendBundle\Migration\UpdateNoteAssociationKindMigration;

class MigrateNotes extends UpdateNoteAssociationKindMigration
{

    /**
     * {@inheritdoc}
     */
    protected function getRenamedClasses(Schema $schema)
    {
        return [
            'Oro\Bundle\CatalogBundle\Entity\Category' => 'OroB2B\Bundle\CatalogBundle\Entity\Category'
        ];
    }
}
