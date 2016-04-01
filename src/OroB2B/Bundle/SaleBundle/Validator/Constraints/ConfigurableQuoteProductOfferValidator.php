<?php

namespace OroB2B\Bundle\SaleBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer as QuoteProductOfferEntity;
use OroB2B\Bundle\SaleBundle\Form\Type\QuoteProductDemandType;

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
            || !array_key_exists(QuoteProductDemandType::FIELD_QUOTE_PRODUCT_OFFER, $value)
            || !$value[QuoteProductDemandType::FIELD_QUOTE_PRODUCT_OFFER] instanceof QuoteProductOfferEntity
        ) {
            $this->context->buildViolation($constraint->blankOfferMessage)
                ->atPath('[' .QuoteProductDemandType::FIELD_QUANTITY. ']')
                ->addViolation();

            return;
        }

        /** @var QuoteProductOfferEntity $offer */
        $offer = $value[QuoteProductDemandType::FIELD_QUOTE_PRODUCT_OFFER];
        $offerQuantity = (float)$offer->getQuantity();
        $quantity = null;
        if (array_key_exists(QuoteProductDemandType::FIELD_QUANTITY, $value)) {
            $quantity = (float)$value[QuoteProductDemandType::FIELD_QUANTITY];
        }

        if ($offer->isAllowIncrements()) {
            if ($offerQuantity > $quantity) {
                $this->context->buildViolation($constraint->lessQuantityMessage)
                    ->atPath('[' . QuoteProductDemandType::FIELD_QUANTITY . ']')
                    ->addViolation();
            }
        } elseif ($offerQuantity !== $quantity) {
            $this->context->buildViolation($constraint->notEqualQuantityMessage)
                ->atPath('[' . QuoteProductDemandType::FIELD_QUANTITY . ']')
                ->addViolation();
        }
    }
}
