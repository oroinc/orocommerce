<?php

namespace Oro\Bundle\PricingBundle\Migrations\Schema\v1_5;

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
            'Oro\Bundle\PricingBundle\Entity\ProductPrice' => 'OroB2B\Bundle\PricingBundle\Entity\ProductPrice',
            'Oro\Bundle\PricingBundle\Entity\PriceList' => 'OroB2B\Bundle\PricingBundle\Entity\PriceList',
            'Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList' => 'OroB2B\Bundle\PricingBundle\Entity' .
                '\PriceAttributePriceList',
        ];
    }
}
