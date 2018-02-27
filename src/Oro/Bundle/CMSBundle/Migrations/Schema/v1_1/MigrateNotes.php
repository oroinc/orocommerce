<?php

namespace Oro\Bundle\CMSBundle\Migrations\Schema\v1_1;

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
            'Oro\Bundle\CMSBundle\Entity\LoginPage' => 'OroB2B\Bundle\CMSBundle\Entity\LoginPage',
            'Oro\Bundle\CMSBundle\Entity\Page'      => 'OroB2B\Bundle\CMSBundle\Entity\Page',
        ];
    }
}
