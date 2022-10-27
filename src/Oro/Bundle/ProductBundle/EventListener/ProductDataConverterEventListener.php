<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\EntityConfigBundle\Manager\AttributeFamilyManager;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeManager;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Strategy\Import\ImportStrategyHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\ImportExport\Event\ProductDataConverterEvent;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Handles product data convert event and clear data of all attributes which not in attribute family.
 */
class ProductDataConverterEventListener
{
    /** @var TranslatorInterface */
    private $translator;

    /** @var AttributeFamilyManager */
    private $attributeFamilyManager;

    /** @var AttributeManager */
    private $attributeManager;

    /** @var ImportStrategyHelper */
    private $strategyHelper;

    public function __construct(
        TranslatorInterface $translator,
        AttributeFamilyManager $attributeFamilyManager,
        AttributeManager $attributeManager,
        ImportStrategyHelper $strategyHelper
    ) {
        $this->translator = $translator;
        $this->attributeFamilyManager = $attributeFamilyManager;
        $this->attributeManager = $attributeManager;
        $this->strategyHelper = $strategyHelper;
    }

    public function onConvertToImport(ProductDataConverterEvent $event): void
    {
        $data = $event->getData();
        if (!isset($data['attributeFamily']['code'])) {
            return;
        }

        $context = $event->getContext();
        if (!$context) {
            return;
        }

        $attributeFamily = $this->attributeFamilyManager->getAttributeFamilyByCode($data['attributeFamily']['code']);
        if (!$attributeFamily) {
            return;
        }

        $clearedAttributes = [];
        foreach ($this->attributeManager->getActiveAttributesByClass(Product::class) as $attribute) {
            if (!isset($data[$attribute->getFieldName()]) ||
                $this->isEmptyValue($data[$attribute->getFieldName()]) ||
                $this->attributeManager->getAttributeByFamilyAndName($attributeFamily, $attribute->getFieldName())
            ) {
                continue;
            }

            $clearedAttributes[] = $this->attributeManager->getAttributeLabel($attribute);
            // Attributes from the family they do not belong to should not have values
            unset($data[$attribute->getFieldName()]);
        }

        $event->setData($data);

        $this->addWarning($context, $clearedAttributes);
    }

    /**
     * @param mixed $value
     * @return bool
     */
    private function isEmptyValue($value): bool
    {
        return (is_array($value) && $value === []) || (is_string($value) && trim($value) === '');
    }

    private function addWarning(ContextInterface $context, array $attributes): void
    {
        if (!$attributes) {
            return;
        }

        $error = $this->translator->trans(
            'oro.product.attribute_family.ignored_attributes.message',
            ['%count%' => count($attributes), '%attributes%' => implode(', ', $attributes)],
            'validators'
        );

        $warningPrefix = $this->translator->trans(
            'oro.importexport.import.warning',
            ['%number%' => $this->strategyHelper->getCurrentRowNumber($context)]
        );

        $this->strategyHelper->addValidationErrors([$error], $context, $warningPrefix);
    }
}
