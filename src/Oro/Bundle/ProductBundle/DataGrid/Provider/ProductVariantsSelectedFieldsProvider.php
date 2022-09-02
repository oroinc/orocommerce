<?php

namespace Oro\Bundle\ProductBundle\DataGrid\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Provider\SelectedFields\SelectedFieldsProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Returns the list of variants fields that should be rendered for the grid of product variants.
 */
class ProductVariantsSelectedFieldsProvider implements SelectedFieldsProviderInterface
{
    private const GRID_NAME = 'product-product-variants-edit';

    private ManagerRegistry $doctrine;

    public function __construct($doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public function getSelectedFields(
        DatagridConfiguration $datagridConfiguration,
        ParameterBag $datagridParameters
    ): array {
        $productId = $datagridParameters->get('parentProduct');
        if ($this->isSupported($datagridConfiguration) && $productId) {
            $productRepository = $this->doctrine->getRepository(Product::class);
            /** @var Product $product */
            $product = $productRepository->find($productId);

            return $product ? $product->getVariantFields() : [];
        }

        return [];
    }


    private function isSupported(DatagridConfiguration $configuration): bool
    {
        return self::GRID_NAME === $configuration->getName();
    }
}
