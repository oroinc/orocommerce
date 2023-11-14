<?php

namespace Oro\Bundle\ProductBundle\ImportExport\EventListener;

use Oro\Bundle\ImportExportBundle\Event\StrategyEvent;
use Oro\Bundle\ImportExportBundle\Strategy\Import\ImportStrategyHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Listener handles logic by showing errors for non-existing kitItems for an existing product kit during product import.
 */
class KitItemsProductStrategyListener
{
    /** @var array|int[] */
    private array $kitItemsIds = [];

    public function __construct(
        private ImportStrategyHelper $strategyHelper,
        private TranslatorInterface $translator,
    ) {
    }

    public function onProcessBefore(StrategyEvent $event): void
    {
        /** @var Product $entity */
        $entity = $event->getEntity();
        if (!$this->isApplicable($entity)) {
            return;
        }

        $errors = $this->processErrors($event);
        if ($errors) {
            $this->strategyHelper->addValidationErrors($errors, $event->getContext());
            $event->setEntity(null);

            return;
        }

        // Stores kit item ids to find missing ones later in onProcessAfter.
        $this->kitItemsIds = $this->getKitItemsIds($entity->getKitItems()->toArray());
    }

    public function onProcessAfter(StrategyEvent $event): void
    {
        /** @var Product $entity */
        $entity = $event->getEntity();
        if (!$this->isApplicable($entity) || !$entity?->getId()) {
            return;
        }

        // Finds missing kit items in the already existing product kit and fails the row if any.
        $errors = [];
        $processedKitItemsIds = $this->getKitItemsIds($entity->getKitItems()->toArray());
        foreach ($this->kitItemsIds as $kitItemId) {
            if (!in_array($kitItemId, $processedKitItemsIds, true)) {
                // Kit item $kitItemId is not found among the processed kit items.
                $errors[] = $this->getKitItemNotFoundError($kitItemId);
            }
        }

        if ($errors) {
            $this->strategyHelper->addValidationErrors($errors, $event->getContext());
            $event->setEntity(null);
        }

        $this->clear();
    }

    private function clear(): void
    {
        $this->kitItemsIds = [];
    }

    private function processErrors(StrategyEvent $event): array
    {
        $errors = [];
        $context = $event->getContext();

        // Checks if there are extra fields or wrong optional values in kit items and fails the row if any.
        $kitItemsExtraFields = $context->getValue(
            KitItemsProductDataConverterEventListener::KIT_ITEMS_EXTRA_FIELDS
        ) ?? [];
        if ($kitItemsExtraFields && is_array($kitItemsExtraFields)) {
            foreach ($kitItemsExtraFields as $kitItemIndex => $extraFields) {
                if (!$extraFields) {
                    continue;
                }

                $errors[] = $this->getKitItemHasUnknownFieldsError($kitItemIndex, $extraFields);
            }
        }


        $kitItemsInvalidValues = $context->getValue(
            KitItemsProductDataConverterEventListener::KIT_ITEMS_INVALID_VALUES
        ) ?? [];
        if ($kitItemsInvalidValues && is_array($kitItemsInvalidValues)) {
            foreach ($kitItemsInvalidValues as $kitItemIndex => $invalidValues) {
                foreach ($invalidValues as $fieldName => $invalidValue) {
                    $errors[] = $this->getKitItemHasInvalidValueError($kitItemIndex, $fieldName, $invalidValue);
                }
            }
        }

        return $errors;
    }

    private function getKitItemsIds(array $kitItems): array
    {
        return array_filter(array_map(static fn ($item) => $item->getId(), $kitItems));
    }

    private function getKitItemNotFoundError(int $id): string
    {
        return $this->translator->trans('oro.product.import.kit_item.not_found', ['%id%' => $id], 'validators');
    }

    private function getKitItemHasUnknownFieldsError(int $index, array $fields): string
    {
        return $this->translator->trans(
            'oro.product.import.kit_item.unknown_fields',
            ['%line%' => $index + 1, '%count%' => count($fields), '{{ fields }}' => implode(', ', $fields)],
            'validators'
        );
    }

    private function getKitItemHasInvalidValueError(int $index, string $fieldName, mixed $invalidValue): string
    {
        return $this->translator->trans(
            sprintf('oro.product.import.kit_item.invalid_value.%s', $fieldName),
            ['%line%' => $index + 1, '{{ field }}' => $fieldName, '{{ value }}' => $invalidValue],
            'validators'
        );
    }

    private function isApplicable(object|null $entity): bool
    {
        return $entity instanceof Product && $entity->getType() === Product::TYPE_KIT;
    }
}
