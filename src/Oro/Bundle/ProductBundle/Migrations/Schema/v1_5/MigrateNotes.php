<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema\v1_5;

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
            'Oro\Bundle\ProductBundle\Entity\Product' => 'OroB2B\Bundle\ProductBundle\Entity\Product',
            'Oro\Bundle\ProductBundle\Entity\ProductImage' => 'OroB2B\Bundle\ProductBundle\Entity\ProductImage',
            'Oro\Bundle\ProductBundle\Entity\ProductUnit' => 'OroB2B\Bundle\ProductBundle\Entity\ProductUnit',
            'Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision' => 'OroB2B\Bundle\ProductBundle' .
                '\Entity\ProductUnitPrecision',
            'Oro\Bundle\ProductBundle\Entity\ProductVariantLink' => 'OroB2B\Bundle\ProductBundle' .
                '\Entity\ProductVariantLink',
        ];
    }
}
