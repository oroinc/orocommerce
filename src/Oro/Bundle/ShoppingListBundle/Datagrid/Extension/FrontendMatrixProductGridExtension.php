<?php

namespace Oro\Bundle\ShoppingListBundle\Datagrid\Extension;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PricingBundle\Layout\DataProvider\FrontendProductPricesProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Layout\DataProvider\ProductListMatrixFormAvailabilityProvider;
use Oro\Bundle\ProductBundle\Provider\ProductVariantAvailabilityProvider;
use Oro\Bundle\ShoppingListBundle\Layout\DataProvider\MatrixGridOrderFormProvider;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListManager;

class FrontendMatrixProductGridExtension extends AbstractExtension
{
    const SUPPORTED_GRID = 'frontend-product-search-grid';
    const MATRIX_FORM_COLUMN_NAME = 'matrixForm';
    const PRODUCT_PRICES_COLUMN_NAME = 'productPrices';

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var ShoppingListManager */
    private $shoppingListManager;

    /** @var MatrixGridOrderFormProvider */
    private $matrixGridOrderFormProvider;

    /** @var ProductListMatrixFormAvailabilityProvider */
    private $productListMatrixFormAvailabilityProvider;

    /** @var ProductVariantAvailabilityProvider */
    private $productVariantAvailabilityProvider;

    /** @var FrontendProductPricesProvider */
    private $frontendProductPricesProvider;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param ShoppingListManager $shoppingListManager
     * @param MatrixGridOrderFormProvider $matrixGridOrderFormProvider
     * @param ProductListMatrixFormAvailabilityProvider $productListMatrixFormAvailabilityProvider
     * @param ProductVariantAvailabilityProvider $productVariantAvailabilityProvider
     * @param FrontendProductPricesProvider $frontendProductPricesProvider
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        ShoppingListManager $shoppingListManager,
        MatrixGridOrderFormProvider $matrixGridOrderFormProvider,
        ProductListMatrixFormAvailabilityProvider $productListMatrixFormAvailabilityProvider,
        ProductVariantAvailabilityProvider $productVariantAvailabilityProvider,
        FrontendProductPricesProvider $frontendProductPricesProvider
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->shoppingListManager = $shoppingListManager;
        $this->matrixGridOrderFormProvider = $matrixGridOrderFormProvider;
        $this->productListMatrixFormAvailabilityProvider = $productListMatrixFormAvailabilityProvider;
        $this->productVariantAvailabilityProvider = $productVariantAvailabilityProvider;
        $this->frontendProductPricesProvider = $frontendProductPricesProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        return 10;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        return static::SUPPORTED_GRID === $config->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function visitResult(DatagridConfiguration $config, ResultsObject $result)
    {
        /** @var ResultRecord[] $rows */
        $rows = $result->getData();
        $productRepository = $this->doctrineHelper->getEntityRepositoryForClass(Product::class);
        $shoppingList = $this->shoppingListManager->getForCurrentUser();

        foreach ($rows as $row) {
            $productId = $row->getValue('id');

            /** @var Product $product */
            $product = $productRepository->find($productId);

            if (!$product) {
                continue;
            }

            if ($this->productListMatrixFormAvailabilityProvider->isInlineMatrixFormAvailable($product)) {
                $simpleProducts = $this->productVariantAvailabilityProvider->getSimpleProductsByVariantFields($product);
                $formHtml = $this->matrixGridOrderFormProvider->getMatrixOrderFormHtml($product, $shoppingList);

                $row->setValue(self::MATRIX_FORM_COLUMN_NAME, $formHtml);
                $row->setValue(
                    self::PRODUCT_PRICES_COLUMN_NAME,
                    $this->frontendProductPricesProvider->getByProducts($simpleProducts)
                );
            }
        }

        $config->addColumn(
            self::PRODUCT_PRICES_COLUMN_NAME,
            ['frontend_type' => 'array', 'type' => 'field', 'renderable' => false, 'translatable' => true]
        );
    }
}
