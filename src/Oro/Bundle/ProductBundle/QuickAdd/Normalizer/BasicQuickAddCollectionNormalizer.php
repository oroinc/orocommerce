<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\QuickAdd\Normalizer;

use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatterInterface;
use Oro\Bundle\ProductBundle\Model\QuickAddRow;
use Oro\Bundle\ProductBundle\Model\QuickAddRowCollection;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Basic normalizer for {@see QuickAddRowCollection}.
 */
class BasicQuickAddCollectionNormalizer implements QuickAddCollectionNormalizerInterface
{
    private LocalizationHelper $localizationHelper;
    private UnitLabelFormatterInterface $unitLabelFormatter;
    private TranslatorInterface $translator;

    public function __construct(
        LocalizationHelper $localizationHelper,
        UnitLabelFormatterInterface $unitLabelFormatter,
        TranslatorInterface $translator
    ) {
        $this->localizationHelper = $localizationHelper;
        $this->unitLabelFormatter = $unitLabelFormatter;
        $this->translator = $translator;
    }

    public function normalize(QuickAddRowCollection $quickAddRowCollection): array
    {
        $results = [
            'errors' => array_map(
                fn (array $error) => [
                    'message' => $this->translator->trans($error['message'], $error['parameters'], 'validators'),
                ],
                $quickAddRowCollection->getErrors()
            ),
            'items' => [],
        ];

        /** @var QuickAddRow $quickAddRow */
        foreach ($quickAddRowCollection as $quickAddRow) {
            $sku = $quickAddRow->getSku();
            $index = $quickAddRow->getIndex();
            $results['items'][$index] = [
                'sku' => $sku,
                'index' => $index,
                'product_name' => '',
                'organization' => $quickAddRow->getOrganization(),
                'quantity' => $quickAddRow->getQuantity(),
                'errors' => array_map(
                    fn (array $error) => [
                        'message' => $this->translator->trans($error['message'], $error['parameters'], 'validators'),
                        'propertyPath' => $error['propertyPath'] ?? '',
                    ],
                    $quickAddRow->getErrors()
                ),
                'additional' => [],
            ];

            $product = $quickAddRow->getProduct();
            if (is_a($product, Product::class)) {
                $results['items'][$index]['product_name'] = (string)$this->localizationHelper
                    ->getLocalizedValue($product->getNames());

                $results['items'][$index]['units'] = $product->getSellUnitsPrecision();
            }

            $unitCode = (string) $quickAddRow->getUnit();
            if (isset($results['items'][$index]['units'][$unitCode])) {
                $results['items'][$index]['unit_label'] = $this->unitLabelFormatter->format($unitCode);
            } else {
                $results['items'][$index]['unit_label'] = $unitCode;
            }

            foreach ($quickAddRow->getAdditionalFields() as $additionalField) {
                $value = $additionalField->getValue();
                $results['items'][$index]['additional'][$additionalField->getName()] = $value;
            }
        }

        return $results;
    }
}
