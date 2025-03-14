<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\RedirectBundle\Migration\Extension\SlugExtensionAwareInterface;
use Oro\Bundle\RedirectBundle\Migration\Extension\SlugExtensionAwareTrait;

class AddProductSlugs implements Migration, SlugExtensionAwareInterface
{
    use SlugExtensionAwareTrait;

    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->slugExtension->addSlugs(
            $schema,
            'oro_product_slug',
            'oro_product',
            'product_id'
        );

        $this->slugExtension->addLocalizedSlugPrototypes(
            $schema,
            'oro_product_slug_prototype',
            'oro_product',
            'product_id'
        );
    }
}
