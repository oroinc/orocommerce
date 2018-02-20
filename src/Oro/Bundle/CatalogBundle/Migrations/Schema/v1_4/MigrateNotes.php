<?php

namespace Oro\Bundle\CatalogBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\NoteBundle\Migration\UpdateNoteAssociationKindForRenamedEntitiesMigration;

class MigrateNotes extends UpdateNoteAssociationKindForRenamedEntitiesMigration
{
    /**
     * {@inheritdoc}
     */
    protected function getRenamedEntitiesNames(Schema $schema)
    {
        return [
            'Oro\Bundle\CatalogBundle\Entity\Category' => 'OroB2B\Bundle\CatalogBundle\Entity\Category'
        ];
    }
}
