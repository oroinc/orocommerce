<?php

namespace Oro\Bundle\ProductBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Form\FormUtil;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Validates duplicated unit precisions for Product entity.
 */
class ValidateDuplicatedProductUnitPrecisions implements ProcessorInterface
{
    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        $includedEntities = $context->getIncludedEntities();
        if (null === $includedEntities) {
            // nothing to check without included entities
            return;
        }

        $unitPrecisionsFieldName = $context->getConfig()->findFieldNameByPropertyPath('unitPrecisions');
        if (!$unitPrecisionsFieldName || !$context->getForm()->has($unitPrecisionsFieldName)) {
            // no field contains unit precisions
            return;
        }

        $data = $context->getData();
        if (empty($data[$unitPrecisionsFieldName])) {
            // unit precisions are not submitted
            return;
        }

        $form = $context->getForm();
        $existingUnitCodes = $this->getExistingUnitCodes($form->getData());
        foreach ($data[$unitPrecisionsFieldName] as $unitPrecisionData) {
            /** @var ProductUnitPrecision|null $unitPrecision */
            $unitPrecision = $includedEntities->get($unitPrecisionData['class'], $unitPrecisionData['id']);
            if (null !== $unitPrecision && null !== $unitPrecision->getUnit()) {
                $unitPrecisionId = $unitPrecision->getId();
                $unitCode = $unitPrecision->getUnit()->getCode();
                if ($this->isExistingUnitCode($existingUnitCodes, $unitCode, $unitPrecisionId)) {
                    FormUtil::addFormError(
                        $form,
                        sprintf('Unit precision "%s" already exists for this product.', $unitCode),
                        $unitPrecisionsFieldName
                    );
                }
                $existingUnitCodes[$unitCode] = $unitPrecisionId;
            }
        }
    }

    private function getExistingUnitCodes(Product $product): array
    {
        $existingUnitCodes = [];
        foreach ($product->getUnitPrecisions() as $unitPrecision) {
            $existingUnitCodes[$unitPrecision->getUnit()->getCode()] = $unitPrecision->getId();
        }

        return $existingUnitCodes;
    }

    private function isExistingUnitCode(array $existingUnitCodes, string $unitCode, ?int $unitPrecisionId): bool
    {
        return
            \array_key_exists($unitCode, $existingUnitCodes)
            && (null === $unitPrecisionId || $unitPrecisionId !== $existingUnitCodes[$unitCode]);
    }
}
