<?php

namespace Oro\Bundle\RFPBundle\Migrations\Schema\v1_6;

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
            'Oro\Bundle\RFPBundle\Entity\Request'            => 'OroB2B\Bundle\RFPBundle\Entity\Request',
            'Oro\Bundle\RFPBundle\Entity\RequestStatus'      => 'OroB2B\Bundle\RFPBundle\Entity\RequestStatus',
            'Oro\Bundle\RFPBundle\Entity\RequestProduct'     => 'OroB2B\Bundle\RFPBundle\Entity\RequestProduct',
            'Oro\Bundle\RFPBundle\Entity\RequestProductItem' => 'OroB2B\Bundle\RFPBundle\Entity\RequestProductItem',
        ];
    }
}
