<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema\v1_34;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityBundle\Migration\UpdateFallbackEntityFieldConfig;
use Oro\Bundle\LayoutBundle\Layout\Extension\ThemeConfiguration as LayoutThemeConfiguration;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\ProductBundle\Entity\Product;

class AddThemeConfigurationFallbackToPageTemplate implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->updatePageTemplateFieldConfig($queries);
    }

    private function updatePageTemplateFieldConfig(QueryBag $queries): void
    {
        $queries->addPostQuery(
            new UpdateFallbackEntityFieldConfig(
                Product::class,
                'pageTemplate',
                'themeConfiguration',
                LayoutThemeConfiguration::buildOptionKey('product_details', 'template')
            )
        );
    }
}
