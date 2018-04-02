<?php

namespace Oro\Bundle\TaxBundle\Migrations\Schema\v1_2;

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
            'Oro\Bundle\TaxBundle\Entity\TaxJurisdiction' => 'OroB2B\Bundle\TaxBundle\Entity\TaxJurisdiction',
            'Oro\Bundle\TaxBundle\Entity\ProductTaxCode'  => 'OroB2B\Bundle\TaxBundle\Entity\ProductTaxCode',
            'Oro\Bundle\TaxBundle\Entity\Tax'             => 'OroB2B\Bundle\TaxBundle\Entity\Tax',
            'Oro\Bundle\TaxBundle\Entity\TaxRule'         => 'OroB2B\Bundle\TaxBundle\Entity\TaxRule',
            'Oro\Bundle\TaxBundle\Entity\ZipCode'         => 'OroB2B\Bundle\TaxBundle\Entity\ZipCode',
            'Oro\Bundle\TaxBundle\Entity\AccountTaxCode'  => 'OroB2B\Bundle\TaxBundle\Entity\AccountTaxCode',
        ];
    }
}
