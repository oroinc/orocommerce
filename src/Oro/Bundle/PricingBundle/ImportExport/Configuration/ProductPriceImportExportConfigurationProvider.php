<?php

namespace Oro\Bundle\PricingBundle\ImportExport\Configuration;

use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfiguration;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationInterface;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationProviderInterface;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProductPriceImportExportConfigurationProvider implements ImportExportConfigurationProviderInterface
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritDoc}
     */
    public function get(): ImportExportConfigurationInterface
    {
        return new ImportExportConfiguration([
            ImportExportConfiguration::FIELD_ENTITY_CLASS => ProductPrice::class,
            ImportExportConfiguration::FIELD_EXPORT_JOB_NAME => 'price_list_product_prices_export_to_csv',
            ImportExportConfiguration::FIELD_EXPORT_PROCESSOR_ALIAS => 'oro_pricing_product_price',
            ImportExportConfiguration::FIELD_EXPORT_TEMPLATE_PROCESSOR_ALIAS => 'oro_pricing_product_price',
            ImportExportConfiguration::FIELD_IMPORT_PROCESSOR_ALIAS => 'oro_pricing_product_price.add_or_replace',
            ImportExportConfiguration::FIELD_IMPORT_JOB_NAME => 'price_list_product_prices_entity_import_from_csv',
            ImportExportConfiguration::FIELD_IMPORT_STRATEGY_TOOLTIP =>
                $this->translator->trans('oro.pricing.productprice.import.strategy.tooltip'),
            ImportExportConfiguration::FIELD_IMPORT_PROCESSORS_TO_CONFIRMATION_MESSAGE => [
                'oro_pricing_product_price.reset' => $this->translator
                    ->trans('oro.pricing.productprice.import.strategy.reset_and_add_confirmation')
            ]
        ]);
    }
}
