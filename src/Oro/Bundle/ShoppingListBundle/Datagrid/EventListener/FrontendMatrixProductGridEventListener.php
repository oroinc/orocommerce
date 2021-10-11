<?php

namespace Oro\Bundle\ShoppingListBundle\Datagrid\EventListener;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PricingBundle\Layout\DataProvider\FrontendProductPricesProvider;
use Oro\Bundle\ProductBundle\DataGrid\DataGridThemeHelper;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\ProductFormAvailabilityProvider;
use Oro\Bundle\SearchBundle\Datagrid\Event\SearchResultAfter;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Layout\DataProvider\MatrixGridOrderFormProvider;
use Oro\Bundle\ShoppingListBundle\Layout\DataProvider\MatrixGridOrderProvider;
use Oro\Bundle\ShoppingListBundle\Manager\CurrentShoppingListManager;

/**
 * Adds data that are required to build a matrix form into the the storefront product grid.
 */
class FrontendMatrixProductGridEventListener
{
    private const MATRIX_FORM_COLUMN_NAME = 'matrixForm';
    private const PRODUCT_PRICES_COLUMN_NAME = 'prices';

    private DoctrineHelper $doctrineHelper;
    private CurrentShoppingListManager $currentShoppingListManager;
    private MatrixGridOrderFormProvider $matrixGridOrderFormProvider;
    private ProductFormAvailabilityProvider $productFormAvailabilityProvider;
    private FrontendProductPricesProvider $frontendProductPricesProvider;
    private MatrixGridOrderProvider $matrixGridOrderProvider;
    private DataGridThemeHelper $dataGridThemeHelper;

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

    public function onPreBuild(PreBuild $event): void
    {
        $config = $event->getConfig();

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

    public function onResultAfter(SearchResultAfter $event): void
    {
        /** @var ResultRecord[] $configurableProductRows */
        /** @var array $configurableProductData */
        [$configurableProductRows, $configurableProductData] = $this->processSimpleProducts($event->getRecords());
        if ($configurableProductRows) {
            /** @var ResultRecord[] $configurableProductWithMatrixFormRows */
            /** @var array $matrixFormTypes */
            [$configurableProductWithMatrixFormRows, $matrixFormTypes] = $this->processConfigurableProducts(
                $configurableProductRows,
                $configurableProductData,
                $event->getDatagrid()->getName()
            );
            if ($configurableProductWithMatrixFormRows) {
                $this->processConfigurableProductsWithMatrixForm(
                    $configurableProductWithMatrixFormRows,
                    $matrixFormTypes
                );
            }
        }
    }

    /**
     * @param ResultRecord[] $rows
     *
     * @return array [configurable product rows, configurable product data]
     */
    private function processSimpleProducts(array $rows): array
    {
        $configurableProductRows = [];
        $configurableProductData = [];
        foreach ($rows as $row) {
            if ($row->getValue('type') === Product::TYPE_CONFIGURABLE) {
                $configurableProductRows[] = $row;
                $configurableProductData[$row->getValue('id')] = [
                    $row->getValue('unit'),
                    $row->getValue('variant_fields_count') ?: 0
                ];
            } else {
                $row->setValue(self::MATRIX_FORM_COLUMN_NAME, ['type' => Configuration::MATRIX_FORM_NONE]);
            }
        }

        return [$configurableProductRows, $configurableProductData];
    }

    /**
     * @param ResultRecord[] $configurableProductRows
     * @param array          $configurableProductData
     * @param string         $datagridName
     *
     * @return array [configurable product with matrix form rows, matrix form data]
     */
    private function processConfigurableProducts(
        array $configurableProductRows,
        array $configurableProductData,
        string $datagridName
    ): array {
        $configurableProductWithMatrixFormRows = [];
        $matrixFormTypes = $this->productFormAvailabilityProvider->getAvailableMatrixFormTypes(
            $configurableProductData,
            $this->dataGridThemeHelper->getTheme($datagridName)
        );
        foreach ($configurableProductRows as $row) {
            $productId = $row->getValue('id');
            if (isset($matrixFormTypes[$productId])) {
                $configurableProductWithMatrixFormRows[] = $row;
            } else {
                $row->setValue(self::MATRIX_FORM_COLUMN_NAME, ['type' => Configuration::MATRIX_FORM_NONE]);
            }
        }

        return [$configurableProductWithMatrixFormRows, $matrixFormTypes];
    }

    /**
     * @param ResultRecord[] $configurableProductWithMatrixFormRows
     * @param array          $matrixFormTypes
     */
    private function processConfigurableProductsWithMatrixForm(
        array $configurableProductWithMatrixFormRows,
        array $matrixFormTypes
    ): void {
        $productRepository = $this->doctrineHelper->getEntityRepositoryForClass(Product::class);
        $shoppingList = $this->currentShoppingListManager->getCurrent();
        foreach ($configurableProductWithMatrixFormRows as $row) {
            $productId = $row->getValue('id');
            $product = $productRepository->find($productId);
            $row->setValue(
                self::MATRIX_FORM_COLUMN_NAME,
                $this->getMatrixFormData($product, $matrixFormTypes[$productId], $shoppingList)
            );
            $row->setValue(
                self::PRODUCT_PRICES_COLUMN_NAME,
                $this->frontendProductPricesProvider->getVariantsPricesByProduct($product)
            );
        }
    }

    private function getMatrixFormData(
        Product $product,
        string $matrixFormType,
        ?ShoppingList $shoppingList
    ): array {
        $matrixFormData = ['type' => $matrixFormType];

        $matrixFormData['totals'] = [
            'quantity' => $this->matrixGridOrderProvider->getTotalQuantity($product, $shoppingList),
            'price' => $this->matrixGridOrderProvider->getTotalPriceFormatted($product, $shoppingList),
        ];

        if ('inline' === $matrixFormType) {
            $matrixFormData['form'] = $this->matrixGridOrderFormProvider->getMatrixOrderFormHtml(
                $product,
                $shoppingList
            );

            $form = $this->matrixGridOrderFormProvider->getMatrixOrderFormView($product, $shoppingList);
            $matrixFormData['rows'][] = count($form['rows'] ?? []);
            $matrixFormData['rows'][] = count($form['rows'][0]['columns'] ?? []);
        }

        return $matrixFormData;
    }
}
