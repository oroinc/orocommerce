<?php

namespace OroB2B\Bundle\SaleBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer as QuoteProductOfferEntity;
use OroB2B\Bundle\SaleBundle\Form\Type\QuoteProductToOrderType;

class ConfigurableQuoteProductOfferValidator extends ConstraintValidator
{
    /**
     * @param ConfigurableQuoteProductOffer $constraint
     *
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!is_array($value)
            || !array_key_exists(QuoteProductToOrderType::FIELD_OFFER, $value)
            || !$value[QuoteProductToOrderType::FIELD_OFFER] instanceof QuoteProductOfferEntity
        ) {
            $this->context->buildViolation($constraint->blankOfferMessage)
                ->atPath('[' .QuoteProductToOrderType::FIELD_QUANTITY. ']')
                ->addViolation();

            return;
        }

        /** @var QuoteProductOfferEntity $offer */
        $offer = $value[QuoteProductToOrderType::FIELD_OFFER];
        $offerQuantity = (float)$offer->getQuantity();
        $quantity = null;
        if (array_key_exists(QuoteProductToOrderType::FIELD_QUANTITY, $value)) {
            $quantity = (float)$value[QuoteProductToOrderType::FIELD_QUANTITY];
        }

        if ($offer->isAllowIncrements()) {
            if ($offerQuantity > $quantity) {
                $this->context->buildViolation($constraint->lessQuantityMessage)
                    ->atPath('[' . QuoteProductToOrderType::FIELD_QUANTITY . ']')
                    ->addViolation();
            }
        } elseif ($offerQuantity !== $quantity) {
            $this->context->buildViolation($constraint->notEqualQuantityMessage)
                ->atPath('[' . QuoteProductToOrderType::FIELD_QUANTITY . ']')
                ->addViolation();
        }
    }
}
