<?php

namespace Oro\Bundle\PricingBundle\Filter;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\PricingBundle\Form\Type\Filter\ProductPriceFilterType;
use Oro\Bundle\PricingBundle\Placeholder\UnitPlaceholder;
use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatter;
use Oro\Bundle\SearchBundle\Datagrid\Filter\SearchNumberRangeFilter;

/**
 * A filter that can be used on frontend`s product grid to get products by prices range.
 */
class FrontendProductPriceFilter extends SearchNumberRangeFilter
{
    /**
     * @var UnitLabelFormatter
     */
    protected $formatter;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * {@inheritdoc}
     */
    protected function getFieldName(array $data)
    {
        $unit = $data['unit'];
        return 'decimal.' . str_replace(UnitPlaceholder::NAME, $unit, $this->get(FilterUtility::DATA_NAME_KEY));
    }

    /**
     * @param UnitLabelFormatter $formatter
     */
    public function setFormatter($formatter)
    {
        $this->formatter = $formatter;
    }

    /**
     * @param ConfigManager $configManager
     */
    public function setConfigManager(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata()
    {
        $metadata = parent::getMetadata();
        $metadata['unitChoices'] = [];

        if ($this->configManager) {
            $metadata['precision'] = (int)$this->configManager->get('oro_pricing.precision');
        }

        $unitChoices = $this->getForm()->createView()['unit']->vars['choices'];
        foreach ($unitChoices as $choice) {
            $metadata['unitChoices'][] = [
                'data' => $choice->data,
                'value' => $choice->value,
                'label' => $choice->label,
                'shortLabel' => $this->formatter->format($choice->value, true),
            ];
        }

        return $metadata;
    }

    /**
     * {@inheritdoc}
     */
    protected function getFormType()
    {
        return ProductPriceFilterType::class;
    }
}
