<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\ImportExport\Configuration;

use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfiguration;
use Oro\Bundle\TaxBundle\Entity\TaxRule;
use Oro\Bundle\TaxBundle\ImportExport\Configuration\TaxRuleImportExportConfigurationProvider;
use PHPUnit\Framework\TestCase;

class TaxRuleImportExportConfigurationProviderTest extends TestCase
{
    public function testGet()
    {
        static::assertEquals(
            new ImportExportConfiguration([
                ImportExportConfiguration::FIELD_ENTITY_CLASS => TaxRule::class,
                ImportExportConfiguration::FIELD_EXPORT_PROCESSOR_ALIAS => 'oro_tax_tax_rule',
                ImportExportConfiguration::FIELD_IMPORT_PROCESSOR_ALIAS => 'oro_tax_tax_rule',
            ]),
            (new TaxRuleImportExportConfigurationProvider())->get()
        );
    }
}
