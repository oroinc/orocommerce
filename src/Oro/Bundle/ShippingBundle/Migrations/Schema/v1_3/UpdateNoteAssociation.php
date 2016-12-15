<?php

namespace Oro\Bundle\ShippingBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\NoteBundle\Migration\UpdateNoteAssociationKindForRenamedEntitiesMigration;

class UpdateNoteAssociation extends UpdateNoteAssociationKindForRenamedEntitiesMigration
{
    /**
     * @return int
     */
    public function getOrder()
    {
        return 4;
    }

    /**
     * {@inheritdoc}
     */
    protected function getRenamedEntitiesNames(Schema $schema)
    {
        return [
            'Oro\Bundle\ShippingBundle\Entity\ShippingRule' =>
            'Oro\Bundle\ShippingBundle\Entity\ShippingMethodConfigsRule',
        ];
    }
}
