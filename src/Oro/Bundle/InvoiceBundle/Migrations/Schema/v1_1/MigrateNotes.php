<?php

namespace Oro\Bundle\InvoiceBundle\Migrations\Schema\v1_1;

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
            'Oro\Bundle\InvoiceBundle\Entity\InvoiceLineItem' => 'OroB2B\Bundle\InvoiceBundle\Entity\InvoiceLineItem',
            'Oro\Bundle\InvoiceBundle\Entity\Invoice'         => 'OroB2B\Bundle\InvoiceBundle\Entity\Invoice',
        ];
    }
}
