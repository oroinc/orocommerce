<?php

namespace Oro\Bundle\CatalogBundle\Migrations\Schema\v1_6;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\RedirectBundle\Migration\Extension\SlugExtension;
use Oro\Bundle\RedirectBundle\Migration\Extension\SlugExtensionAwareInterface;

class AddCategorySlugs implements Migration, SlugExtensionAwareInterface
{
    /**
     * @var SlugExtension
     */
    protected $slugExtension;

    /**
     * {@inheritdoc}
     */
    public function setSlugExtension(SlugExtension $extension)
    {
        $this->slugExtension = $extension;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->slugExtension->addSlugs(
            $schema,
            'oro_catalog_cat_slug',
            'oro_catalog_category',
            'category_id'
        );

        $this->slugExtension->addLocalizedSlugPrototypes(
            $schema,
            'oro_catalog_cat_slug_prototype',
            'oro_catalog_category',
            'category_id'
        );
    }
}
