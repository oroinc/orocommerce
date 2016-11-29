<?php

namespace Oro\Bundle\OrderBundle\Migrations\Schema\v1_5;

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
            'Oro\Bundle\OrderBundle\Entity\OrderDiscount' => 'OroB2B\Bundle\OrderBundle\Entity\OrderDiscount',
            'Oro\Bundle\OrderBundle\Entity\Order'         => 'OroB2B\Bundle\OrderBundle\Entity\Order',
            'Oro\Bundle\OrderBundle\Entity\OrderLineItem' => 'OroB2B\Bundle\OrderBundle\Entity\OrderLineItem',
            'Oro\Bundle\OrderBundle\Entity\OrderAddress'  => 'OroB2B\Bundle\OrderBundle\Entity\OrderAddress',
        ];
    }
}
