<?php

namespace Oro\Bundle\InvoiceBundle\Migrations\Schema\v1_1;

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
            'Oro\Bundle\InvoiceBundle\Entity\InvoiceLineItem' => 'OroB2B\Bundle\InvoiceBundle\Entity\InvoiceLineItem',
            'Oro\Bundle\InvoiceBundle\Entity\Invoice'         => 'OroB2B\Bundle\InvoiceBundle\Entity\Invoice',
        ];
    }
}
