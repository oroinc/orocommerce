<?php

namespace Oro\Bundle\PricingBundle\Filter;

use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\PricingBundle\Form\Type\Filter\ProductPriceFilterType;
use Oro\Bundle\PricingBundle\Placeholder\UnitPlaceholder;
use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatterInterface;
use Oro\Bundle\SearchBundle\Datagrid\Filter\SearchNumberRangeFilter;
use Symfony\Component\Form\FormFactoryInterface;

/**
 * The filter by a product price on the storefront.
 */
class FrontendProductPriceFilter extends SearchNumberRangeFilter
{
    /** @var UnitLabelFormatterInterface */
    private $formatter;

    public function __construct(
        FormFactoryInterface $factory,
        FilterUtility $util,
        UnitLabelFormatterInterface $formatter
    ) {
        parent::__construct($factory, $util);
        $this->formatter = $formatter;
    }

    /**
     * {@inheritdoc}
     */
    protected function getFieldName(array $data)
    {
        $unit = $data['unit'];
        return 'decimal.' . str_replace(UnitPlaceholder::NAME, $unit, $this->get(FilterUtility::DATA_NAME_KEY));
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata()
    {
        $metadata = parent::getMetadata();

        $metadata['unitChoices'] = [];
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
