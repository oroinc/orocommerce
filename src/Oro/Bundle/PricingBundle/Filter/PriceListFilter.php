<?php

namespace Oro\Bundle\PricingBundle\Filter;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Filter\SingleChoiceFilter;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Form\Type\Filter\PriceListFilterType;
use Symfony\Component\Form\FormFactoryInterface;

/**
 * The filter by a price list.
 */
class PriceListFilter extends SingleChoiceFilter
{
    private ManagerRegistry $doctrine;

    public function __construct(FormFactoryInterface $factory, FilterUtility $util, ManagerRegistry $doctrine)
    {
        parent::__construct($factory, $util);
        $this->doctrine = $doctrine;
    }

    /**
     * {@inheritDoc}
     */
    public function init($name, array $params)
    {
        $params[FilterUtility::FRONTEND_TYPE_KEY] = 'choice';

        parent::init($name, $params);
    }

    /**
     * {@inheritDoc}
     */
    public function prepareData(array $data): array
    {
        $data = parent::prepareData($data);
        if (isset($data['value'])) {
            $data['value'] = $this->doctrine
                ->getManagerForClass(PriceList::class)
                ->getReference(PriceList::class, (int)$data['value']);
        }

        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function getMetadata()
    {
        $metadata = parent::getMetadata();

        $formView = $this->getFormView();

        // Allow clearing if filter is not required, disallow otherwise.
        $metadata['allowClear'] = false;
        if (isset($formView->vars['required'])) {
            $metadata['allowClear'] = !$formView->vars['required'];
        }

        // Ensure default value is selected in dropdown.
        if (isset($formView->vars['value']['value'])) {
            $metadata['value'] = ['value' => (string) $formView->vars['value']['value']];
        }

        return $metadata;
    }

    /**
     * {@inheritDoc}
     */
    protected function getFormType(): string
    {
        return PriceListFilterType::class;
    }
}
