<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\ImportExport\Configuration;

use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfiguration;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\ImportExport\Configuration\CouponImportExportConfigurationProvider;
use PHPUnit\Framework\TestCase;

class CouponImportExportConfigurationProviderTest extends TestCase
{
    public function testGet()
    {
        static::assertEquals(
            new ImportExportConfiguration([
                ImportExportConfiguration::FIELD_ENTITY_CLASS => Coupon::class,
                ImportExportConfiguration::FIELD_EXPORT_TEMPLATE_PROCESSOR_ALIAS =>
                    'oro_promotion_coupon_export_template',
                ImportExportConfiguration::FIELD_EXPORT_PROCESSOR_ALIAS => 'oro_promotion_coupon_export',
                ImportExportConfiguration::FIELD_IMPORT_PROCESSOR_ALIAS => 'oro_promotion_coupon_import',
            ]),
            (new CouponImportExportConfigurationProvider())->get()
        );
    }
}
