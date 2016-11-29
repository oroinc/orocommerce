<?php

namespace Oro\Bundle\PaymentBundle\Migrations\Schema\v1_4;

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
            'Oro\Bundle\PaymentBundle\Entity\PaymentTransaction' => 'OroB2B\Bundle\PaymentBundle' .
                '\Entity\PaymentTransaction',
            'Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm' => 'OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm'
        ];
    }
}
