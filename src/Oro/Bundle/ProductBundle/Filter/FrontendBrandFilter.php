<?php

namespace Oro\Bundle\ProductBundle\Filter;

use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\ProductBundle\Entity\Brand;
use Oro\Bundle\ProductBundle\Entity\Repository\BrandRepository;
use Oro\Bundle\SearchBundle\Datagrid\Filter\SearchEntityFilter;

/**
 * This class point out a customizing form type for brand filter.
 */
class FrontendBrandFilter extends SearchEntityFilter
{
    public const FILTER_ALIAS = 'frontend_brand';

    #[\Override]
    public function init($name, array $params): void
    {
        $params[FilterUtility::FORM_OPTIONS_KEY]['field_options'] = $this->getDefaultFieldOptions();

        parent::init($name, $params);
    }

    private function getDefaultFieldOptions(): array
    {
        /** @var BrandRepository $brandRepository */
        $brandRepository = $this->doctrine->getRepository(Brand::class);

        return ['query_builder' => $brandRepository->getBrandQueryBuilder()];
    }
}
