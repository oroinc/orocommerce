<?php

namespace Oro\Bundle\CMSBundle\Migrations\Schema\v1_1;

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
            'Oro\Bundle\CMSBundle\Entity\LoginPage' => 'OroB2B\Bundle\CMSBundle\Entity\LoginPage',
            'Oro\Bundle\CMSBundle\Entity\Page'      => 'OroB2B\Bundle\CMSBundle\Entity\Page',
        ];
    }
}
