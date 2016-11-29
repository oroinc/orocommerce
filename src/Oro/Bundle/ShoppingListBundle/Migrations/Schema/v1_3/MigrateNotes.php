<?php

namespace Oro\Bundle\ShoppingListBundle\Migrations\Schema\v1_3;

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
            'Oro\Bundle\ShoppingListBundle\Entity\LineItem' => 'OroB2B\Bundle\ShoppingListBundle\Entity\LineItem',
            'Oro\Bundle\ShoppingListBundle\Entity\ShoppingList' => 'OroB2B\Bundle\ShoppingListBundle' .
                '\Entity\ShoppingList'
        ];
    }
}
