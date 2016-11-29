<?php

namespace Oro\Bundle\RedirectBundle\Migrations\Schema\v1_1;

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
            'Oro\Bundle\RedirectBundle\Entity\Slug' => 'OroB2B\Bundle\RedirectBundle\Entity\Slug'
        ];
    }
}
