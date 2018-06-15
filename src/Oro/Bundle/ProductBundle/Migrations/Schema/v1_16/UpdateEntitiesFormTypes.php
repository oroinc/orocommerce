<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema\v1_16;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigEntityValueQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\ProductBundle\Entity\Brand;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\BrandSelectType;
use Oro\Bundle\ProductBundle\Form\Type\ProductSelectType;

class UpdateEntitiesFormTypes implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addPostQuery(
            new UpdateEntityConfigEntityValueQuery(
                Brand::class,
                'form',
                'form_type',
                BrandSelectType::class,
                'oro_product_brand_select'
            )
        );

        $queries->addPostQuery(
            new UpdateEntityConfigEntityValueQuery(
                Product::class,
                'form',
                'form_type',
                ProductSelectType::class,
                'oro_product_select'
            )
        );
    }
}
