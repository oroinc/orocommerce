<?php

namespace Oro\Bundle\ProductBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Collection\IncludedEntityCollection;
use Oro\Bundle\ApiBundle\Form\FormUtil;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Adds event listener for Product entity form to validate duplicated unit precisions.
 */
class AddValidateUnitPrecisionsDuplicateFormListener implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var FormContext $context */

        $formBuilder = $context->getFormBuilder();
        if (!$formBuilder) {
            // the form builder does not exist
            return;
        }

        $includedEntities = $context->getIncludedEntities();
        if (null === $includedEntities) {
            // nothing to check without included entities
            return;
        }

        // get submitted field name (it might be renamed in a customization)
        $unitPrecisionsFieldName = $context->getConfig()->findFieldNameByPropertyPath('unitPrecisions');
        // add listener if the form builder has unit precision field
        if ($unitPrecisionsFieldName && $formBuilder->has($unitPrecisionsFieldName)) {
            $formBuilder->get($unitPrecisionsFieldName)->addEventListener(
                FormEvents::PRE_SUBMIT,
                fn (FormEvent $event) => $this->onPreSubmit($event, $includedEntities, $context->getId())
            );
        }
    }

    private function onPreSubmit(FormEvent $event, IncludedEntityCollection $includedEntities, ?string $objectId)
    {
        /** @var array $unitPrecisionsData [['class' => unit precision class name, 'id' => unit precision id], ...] */
        $unitPrecisionsData = $event->getData();
        if (empty($unitPrecisionsData)) {
            // unit precisions are not submitted
            return;
        }

        /** @var array $existingUnitCodes [unit code => unit precision id, ...] */
        $existingUnitCodes = [];

        /** @var Product $product */
        $product = $includedEntities->get(Product::class, $objectId) ?? $includedEntities->getPrimaryEntity();
        foreach ($product->getUnitPrecisions() as $unitPrecision) {
            $existingUnitCodes[$unitPrecision->getUnit()->getCode()] = $unitPrecision->getId();
        }

        foreach ($unitPrecisionsData as $unitPrecisionData) {
            /** @var ProductUnitPrecision|null $unitPrecision */
            $unitPrecision = $includedEntities->get($unitPrecisionData['class'], $unitPrecisionData['id']);
            if (null !== $unitPrecision && null !== $unitPrecision->getUnit()) {
                $unitPrecisionId = $unitPrecision->getId();
                $unitCode = $unitPrecision->getUnit()->getCode();
                if (\array_key_exists($unitCode, $existingUnitCodes)
                    && (null === $unitPrecisionId || $unitPrecisionId !== $existingUnitCodes[$unitCode])
                ) {
                    FormUtil::addFormError(
                        $event->getForm(),
                        sprintf('Unit precision "%s" already exists for this product.', $unitCode)
                    );
                }
                $existingUnitCodes[$unitCode] = $unitPrecisionId;
            }
        }
    }
}
