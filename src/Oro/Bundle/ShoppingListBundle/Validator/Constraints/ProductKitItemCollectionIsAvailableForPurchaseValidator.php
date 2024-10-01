<?php

namespace Oro\Bundle\ShoppingListBundle\Validator\Constraints;

use Oro\Bundle\FormBundle\Utils\ValidationGroupUtils;
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
    private TranslationMessageSanitizerInterface $translationMessageSanitizer;

    public function __construct(TranslationMessageSanitizerInterface $translationMessageSanitizer)
    {
        $this->translationMessageSanitizer = $translationMessageSanitizer;
    }

    /**
     * @param iterable<ProductKitItem>|null $value
     * @param ProductKitItemCollectionIsAvailableForPurchase $constraint
     */
    #[\Override]
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
        $validationGroups = ValidationGroupUtils::resolveValidationGroups($constraint->validationGroups);
        foreach ($value as $kitItem) {
            $kitItemsCount++;
            $constraintViolations = $validator->validate($kitItem, null, $validationGroups);
            if ($constraintViolations->count() > 0) {
                $unavailableKitItemsCount++;

                if ($productKitSku === null) {
                    $productKitSku = $this->translationMessageSanitizer->sanitizeMessage(
                        (string)$kitItem->getProductKit()?->getSku()
                    );
                }

                if ($kitItem->isOptional() === false) {
                    $reason = array_map(
                        fn (ConstraintViolationInterface $violation) =>
                            $this->translationMessageSanitizer->sanitizeMessage($violation->getMessage()),
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
}
