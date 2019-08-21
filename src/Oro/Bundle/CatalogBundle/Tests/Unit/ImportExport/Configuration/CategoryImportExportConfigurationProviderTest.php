<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\ImportExport\Configuration;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\ImportExport\Configuration\CategoryImportExportConfigurationProvider;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfiguration;

class CategoryImportExportConfigurationProviderTest extends \PHPUnit\Framework\TestCase
{
    public function testGet()
    {
        $this->assertEquals(
            new ImportExportConfiguration([
                ImportExportConfiguration::FIELD_ENTITY_CLASS => Category::class,
                ImportExportConfiguration::FIELD_EXPORT_PROCESSOR_ALIAS => 'oro_category',
                ImportExportConfiguration::FIELD_EXPORT_TEMPLATE_PROCESSOR_ALIAS => 'oro_category',
                ImportExportConfiguration::FIELD_IMPORT_PROCESSOR_ALIAS => 'oro_category.add_or_replace',
                ImportExportConfiguration::FIELD_IMPORT_JOB_NAME => 'category_import_from_csv',
            ]),
            (new CategoryImportExportConfigurationProvider())->get()
        );
    }
}
