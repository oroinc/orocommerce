<?php

namespace Oro\Bundle\PricingBundle\ImportExport\Configuration;

use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfiguration;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationInterface;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationProviderInterface;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use Symfony\Contracts\Translation\TranslatorInterface;

class PriceAttributeProductPriceImportExportConfigurationProvider implements ImportExportConfigurationProviderInterface
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
            ImportExportConfiguration::FIELD_ENTITY_CLASS => PriceAttributeProductPrice::class,
            ImportExportConfiguration::FIELD_EXPORT_PROCESSOR_ALIAS => 'oro_pricing_product_price_attribute_price',
            ImportExportConfiguration::FIELD_EXPORT_TEMPLATE_PROCESSOR_ALIAS =>
                'oro_pricing_product_price_attribute_price',
            ImportExportConfiguration::FIELD_IMPORT_JOB_NAME => 'price_attribute_product_price_import_from_csv',
            ImportExportConfiguration::FIELD_IMPORT_PROCESSOR_ALIAS =>
                'oro_pricing_product_price_attribute_price.add_or_replace',
            ImportExportConfiguration::FIELD_IMPORT_VALIDATION_BUTTON_LABEL =>
                $this->translator->trans('oro.pricing.priceattributeproductprice.import_validation.button.label'),
            ImportExportConfiguration::FIELD_EXPORT_BUTTON_LABEL =>
                $this->translator->trans('oro.pricing.priceattributeproductprice.export.button.label'),
            ImportExportConfiguration::FIELD_IMPORT_ENTITY_LABEL =>
                $this->translator->trans('oro.pricing.priceattributeproductprice.import.entity.label'),
            ImportExportConfiguration::FIELD_IMPORT_STRATEGY_TOOLTIP =>
                $this->translator->trans('oro.pricing.priceattributeproductprice.import.strategy.tooltip'),
            ImportExportConfiguration::FIELD_IMPORT_PROCESSORS_TO_CONFIRMATION_MESSAGE => [
                'oro_pricing_product_price_attribute_price.reset' => $this->translator
                    ->trans('oro.pricing.priceattributeproductprice.import.strategy.reset_and_add_confirmation')
            ]
        ]);
    }
}
