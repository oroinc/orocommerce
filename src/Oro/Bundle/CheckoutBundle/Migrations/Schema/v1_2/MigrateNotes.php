<?php

namespace Oro\Bundle\CheckoutBundle\Migrations\Schema\v1_2;

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
            'Oro\Bundle\CheckoutBundle\Entity\Checkout'       => 'OroB2B\Bundle\CheckoutBundle\Entity\Checkout',
            'Oro\Bundle\CheckoutBundle\Entity\CheckoutSource' => 'OroB2B\Bundle\CheckoutBundle\Entity\CheckoutSource',
        ];
    }
}
