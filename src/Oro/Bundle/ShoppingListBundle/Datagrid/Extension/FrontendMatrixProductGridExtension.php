<?php

namespace Oro\Bundle\ShoppingListBundle\Datagrid\Extension;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PricingBundle\Layout\DataProvider\FrontendProductPricesProvider;
use Oro\Bundle\ProductBundle\DataGrid\DataGridThemeHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Layout\DataProvider\ProductFormAvailabilityProvider;
use Oro\Bundle\ShoppingListBundle\Layout\DataProvider\MatrixGridOrderFormProvider;
use Oro\Bundle\ShoppingListBundle\Layout\DataProvider\MatrixGridOrderProvider;
use Oro\Bundle\ShoppingListBundle\Manager\CurrentShoppingListManager;

/**
 * This extension adds data, that is required to build a matrix form, into a product search grid at frontend
 */
class FrontendMatrixProductGridExtension extends AbstractExtension
{
    const SUPPORTED_GRID = 'frontend-product-search-grid';
    const MATRIX_FORM_TYPE_COLUMN_NAME = 'matrixFormType';
    const MATRIX_FORM_COLUMN_NAME = 'matrixForm';
    const PRODUCT_PRICES_COLUMN_NAME = 'prices';

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var CurrentShoppingListManager */
    private $currentShoppingListManager;

    /** @var MatrixGridOrderFormProvider */
    private $matrixGridOrderFormProvider;

    /** @var ProductFormAvailabilityProvider */
    private $productFormAvailabilityProvider;

    /** @var FrontendProductPricesProvider */
    private $frontendProductPricesProvider;

    /** @var MatrixGridOrderProvider */
    private $matrixGridOrderProvider;

    /** @var DataGridThemeHelper */
    private $dataGridThemeHelper;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        CurrentShoppingListManager $currentShoppingListManager,
        MatrixGridOrderFormProvider $matrixGridOrderFormProvider,
        ProductFormAvailabilityProvider $productFormAvailabilityProvider,
        FrontendProductPricesProvider $frontendProductPricesProvider,
        MatrixGridOrderProvider $matrixGridOrderProvider,
        DataGridThemeHelper $dataGridThemeHelper
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->currentShoppingListManager = $currentShoppingListManager;
        $this->matrixGridOrderFormProvider = $matrixGridOrderFormProvider;
        $this->productFormAvailabilityProvider = $productFormAvailabilityProvider;
        $this->frontendProductPricesProvider = $frontendProductPricesProvider;
        $this->matrixGridOrderProvider = $matrixGridOrderProvider;
        $this->dataGridThemeHelper = $dataGridThemeHelper;
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
        if (!parent::isApplicable($config)) {
            return false;
        }

        return static::SUPPORTED_GRID === $config->getName() || $config->isDatagridExtendedFrom(self::SUPPORTED_GRID);
    }

    /**
     * {@inheritdoc}
     */
    public function visitResult(DatagridConfiguration $config, ResultsObject $result)
    {
        /** @var ResultRecord[] $rows */
        $rows = $result->getData();
        $productRepository = $this->doctrineHelper->getEntityRepositoryForClass(Product::class);
        $shoppingList = $this->currentShoppingListManager->getCurrent();

        foreach ($rows as $row) {
            $productId = $row->getValue('id');

            /** @var Product $product */
            $product = $productRepository->find($productId);

            $matrixFormData = [
                'type' => 'none',
            ];

            if ($product && $this->productFormAvailabilityProvider->isMatrixFormAvailable($product)) {
                $productView = $this->dataGridThemeHelper->getTheme($config->getName());
                $matrixFormData['type'] = $this->productFormAvailabilityProvider
                    ->getAvailableMatrixFormType($product, $productView);
                $matrixFormData['totals'] = [
                    'quantity' => $this->matrixGridOrderProvider->getTotalQuantity($product),
                    'price' => $this->matrixGridOrderProvider->getTotalPriceFormatted($product),
                ];

                if ($matrixFormData['type'] === 'inline') {
                    $formHtml = $this->matrixGridOrderFormProvider->getMatrixOrderFormHtml($product, $shoppingList);
                    $matrixFormData['form'] = $formHtml;

                    $form = $this->matrixGridOrderFormProvider->getMatrixOrderFormView($product, $shoppingList);
                    $matrixFormData['rows'][] = count($form['rows'] ?? []);
                    $matrixFormData['rows'][] = count($form['rows'][0]['columns'] ?? []);
                }

                $row->setValue(
                    self::PRODUCT_PRICES_COLUMN_NAME,
                    $this->frontendProductPricesProvider->getVariantsPricesByProduct($product)
                );
            }

            $row->setValue(self::MATRIX_FORM_COLUMN_NAME, $matrixFormData);
        }

        $config->offsetAddToArrayByPath(
            '[properties]',
            [
                self::PRODUCT_PRICES_COLUMN_NAME => [
                    'type' => 'field',
                    'frontend_type' => PropertyInterface::TYPE_ROW_ARRAY
                ]
            ]
        );

        $config->offsetAddToArrayByPath(
            '[properties]',
            [
                self::MATRIX_FORM_COLUMN_NAME => [
                    'type' => 'field',
                    'frontend_type' => PropertyInterface::TYPE_ROW_ARRAY
                ]
            ]
        );
    }
}
