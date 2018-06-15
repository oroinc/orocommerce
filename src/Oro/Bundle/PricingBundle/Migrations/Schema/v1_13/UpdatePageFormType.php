<?php

namespace Oro\Bundle\PricingBundle\Migrations\Schema\v1_13;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigEntityValueQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Form\Type\PriceListSelectType;

class UpdatePageFormType implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addPostQuery(
            new UpdateEntityConfigEntityValueQuery(
                PriceList::class,
                'form',
                'form_type',
                PriceListSelectType::class,
                'oro_pricing_price_list_select'
            )
        );
    }
}
