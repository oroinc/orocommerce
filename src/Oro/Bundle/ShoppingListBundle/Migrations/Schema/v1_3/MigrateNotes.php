<?php

namespace Oro\Bundle\ShoppingListBundle\Migrations\Schema\v1_3;

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
            'Oro\Bundle\ShoppingListBundle\Entity\LineItem' => 'OroB2B\Bundle\ShoppingListBundle\Entity\LineItem',
            'Oro\Bundle\ShoppingListBundle\Entity\ShoppingList' => 'OroB2B\Bundle\ShoppingListBundle' .
                '\Entity\ShoppingList'
        ];
    }
}
