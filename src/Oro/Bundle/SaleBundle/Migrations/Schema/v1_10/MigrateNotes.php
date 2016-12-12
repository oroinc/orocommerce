<?php

namespace Oro\Bundle\SaleBundle\Migrations\Schema\v1_10;

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
            'Oro\Bundle\SaleBundle\Entity\Quote'        => 'OroB2B\Bundle\SaleBundle\Entity\Quote',
            'Oro\Bundle\SaleBundle\Entity\QuoteAddress' => 'OroB2B\Bundle\SaleBundle\Entity\QuoteAddress',
        ];
    }
}
