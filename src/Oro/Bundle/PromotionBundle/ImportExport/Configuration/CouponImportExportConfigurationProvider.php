<?php

namespace Oro\Bundle\PromotionBundle\ImportExport\Configuration;

use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfiguration;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationInterface;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationProviderInterface;
use Oro\Bundle\PromotionBundle\Entity\Coupon;

class CouponImportExportConfigurationProvider implements ImportExportConfigurationProviderInterface
{
    /**
     * {@inheritDoc}
     *
     * @throws \InvalidArgumentException
     */
    public function get(): ImportExportConfigurationInterface
    {
        return new ImportExportConfiguration([
            ImportExportConfiguration::FIELD_ENTITY_CLASS => Coupon::class,
            ImportExportConfiguration::FIELD_EXPORT_TEMPLATE_PROCESSOR_ALIAS => 'oro_promotion_coupon_export_template',
            ImportExportConfiguration::FIELD_EXPORT_PROCESSOR_ALIAS => 'oro_promotion_coupon_export',
            ImportExportConfiguration::FIELD_IMPORT_PROCESSOR_ALIAS => 'oro_promotion_coupon_import',
        ]);
    }
}
