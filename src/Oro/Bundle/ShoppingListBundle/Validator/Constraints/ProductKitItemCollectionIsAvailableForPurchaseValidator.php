<?php

namespace Oro\Bundle\ShoppingListBundle\Validator\Constraints;

use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\TranslationBundle\Translation\TranslationMessageSanitizerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Checks that collection of {@see ProductKitItem} entities is available for purchase.
 */
class ProductKitItemCollectionIsAvailableForPurchaseValidator extends ConstraintValidator
{
    private LocalizationHelper $localizationHelper;

    private ?TranslationMessageSanitizerInterface $translationMessageSanitizer = null;

    public function __construct(LocalizationHelper $localizationHelper)
    {
        $this->localizationHelper = $localizationHelper;
    }

    public function setTranslationMessageSanitizer(
        ?TranslationMessageSanitizerInterface $translationMessageSanitizer
    ): void {
        $this->translationMessageSanitizer = $translationMessageSanitizer;
    }

    /**
     * @param iterable<ProductKitItem>|null $value
     * @param ProductKitItemCollectionIsAvailableForPurchase $constraint
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof ProductKitItemCollectionIsAvailableForPurchase) {
            throw new UnexpectedTypeException($constraint, ProductKitItemCollectionIsAvailableForPurchase::class);
        }

        if (!is_iterable($value)) {
            throw new UnexpectedValueException($value, 'iterable');
        }

        $validator = $this->context->getValidator();
        $kitItemsCount = $unavailableKitItemsCount = 0;
        $productKitSku = null;
        foreach ($value as $kitItem) {
            $kitItemsCount++;
            $constraintViolations = $validator
                ->validate($kitItem, null, ['product_kit_item_is_available_for_purchase']);
            if ($constraintViolations->count() > 0) {
                $unavailableKitItemsCount++;

                if ($productKitSku === null) {
                    $productKitSku = $this->sanitizeMessage((string)$kitItem->getProductKit()?->getSku());
                }

                if ($kitItem->isOptional() === false) {
                    $reason = array_map(
                        fn (ConstraintViolationInterface $violation) => $this->sanitizeMessage(
                            $violation->getMessage()
                        ),
                        iterator_to_array($constraintViolations)
                    );
                    $this->context
                        ->buildViolation($constraint->requiredKitItemNotAvailableMessage)
                        ->setParameter('{{ product_kit_sku }}', $this->formatValue($productKitSku))
                        ->setParameter('{{ reason }}', $this->formatValues($reason))
                        ->setCode(ProductKitItemCollectionIsAvailableForPurchase::REQUIRED_KIT_ITEM_NOT_AVAILABLE_ERROR)
                        ->setCause($constraintViolations)
                        ->addViolation();
                }
            }
        }

        if ($kitItemsCount === $unavailableKitItemsCount && $this->context->getViolations()->count() === 0) {
            $this->context
                ->buildViolation($constraint->noAvailableKitItemsMessage)
                ->setCode(ProductKitItemCollectionIsAvailableForPurchase::NO_AVAILABLE_KIT_ITEMS_ERROR)
                ->addViolation();
        }
    }

    private function sanitizeMessage(string $message): string
    {
        return $this->translationMessageSanitizer !== null
            ? $this->translationMessageSanitizer->sanitizeMessage($message)
            : $message;
    }
}
