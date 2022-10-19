<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Provider\QuickAdd;

use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\QuickAddRow;
use Oro\Bundle\ProductBundle\Model\QuickAddRowCollection;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides basic data from {@see QuickAddRowCollection}.
 */
class BasicQuickAddImportResultsProvider implements QuickAddImportResultsProviderInterface
{
    private LocalizationHelper $localizationHelper;

    private TranslatorInterface $translator;

    public function __construct(LocalizationHelper $localizationHelper, TranslatorInterface $translator)
    {
        $this->localizationHelper = $localizationHelper;
        $this->translator = $translator;
    }

    public function getResults(QuickAddRowCollection $quickAddRowCollection): array
    {
        $results = [];

        /** @var QuickAddRow $quickAddRow */
        foreach ($quickAddRowCollection as $quickAddRow) {
            $sku = $quickAddRow->getSku();
            $index = $this->getIndex($quickAddRow);
            $results[$index] = [
                'sku' => $sku,
                'product_name' => '',
                'unit' => (string) $quickAddRow->getUnit(),
                'quantity' => $quickAddRow->getQuantity(),
                'errors' => array_map(
                    fn (array $error) => array_merge([
                        'message' => $this->translator->trans($error['message'], $error['parameters'], 'validators'),
                        'propertyPath' => $error['propertyPath'],
                    ]),
                    $quickAddRow->getErrors()
                ),
                'additional' => [],
            ];

            $product = $quickAddRow->getProduct();
            if (is_a($product, Product::class)) {
                $results[$index]['product_name'] = (string)$this->localizationHelper
                    ->getLocalizedValue($product->getNames());

                foreach ($product->getUnitPrecisions()->toArray() as $unitPrecision) {
                    $results[$index]['units'][$unitPrecision->getProductUnitCode()] = $unitPrecision->getPrecision();
                }
            }

            foreach ($quickAddRow->getAdditionalFields() as $additionalField) {
                $value = $additionalField->getValue();
                $results[$index]['additional'][$additionalField->getName()] = $value;
            }
        }

        return $results;
    }

    private function getIndex(QuickAddRow $quickAddRow): string
    {
        return sprintf('%s_%s', mb_strtoupper($quickAddRow->getSku()), $quickAddRow->getUnit());
    }
}
