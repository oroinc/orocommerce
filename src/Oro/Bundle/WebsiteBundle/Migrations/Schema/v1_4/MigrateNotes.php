<?php

namespace Oro\Bundle\WebsiteBundle\Migrations\Schema\v1_4;

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
            'Oro\Bundle\WebsiteBundle\Entity\Website' => 'OroB2B\Bundle\WebsiteBundle\Entity\Website'
        ];
    }
}
