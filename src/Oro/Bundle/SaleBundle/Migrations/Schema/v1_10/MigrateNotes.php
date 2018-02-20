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
            'Oro\Bundle\SaleBundle\Entity\Quote'               => 'OroB2B\Bundle\SaleBundle\Entity\Quote',
            'Oro\Bundle\SaleBundle\Entity\QuoteDemand'         => 'OroB2B\Bundle\SaleBundle\Entity\QuoteDemand',
            'Oro\Bundle\SaleBundle\Entity\QuoteProduct'        => 'OroB2B\Bundle\SaleBundle\Entity\QuoteProduct',
            'Oro\Bundle\SaleBundle\Entity\QuoteAddress'        => 'OroB2B\Bundle\SaleBundle\Entity\QuoteAddress',
            'Oro\Bundle\SaleBundle\Entity\QuoteProductOffer'   => 'OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer',
            'Oro\Bundle\SaleBundle\Entity\QuoteProductRequest' => 'OroB2B\Bundle\SaleBundle\Entity\QuoteProductRequest',
        ];
    }
}
