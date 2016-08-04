<?php

namespace OroB2B\Bundle\PricingBundle\Validator\Constraints;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

use OroB2B\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use OroB2B\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use OroB2B\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;

class PriceListProductPricesCurrencyValidator extends ConstraintValidator
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @param Registry $registry
     */
    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param ProductPrice|object $value
     * @param ProductPriceCurrency $constraint
     *
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$value instanceof PriceAttributePriceList) {
            throw new UnexpectedTypeException($value, 'OroB2B\Bundle\PricingBundle\Entity\PriceAttributePriceList');
        }

        /** @var ProductPriceRepository $repository */
        $repository = $this->registry->getManagerForClass(PriceAttributeProductPrice::class)
            ->getRepository(PriceAttributeProductPrice::class);
        $invalidCurrencies = $repository->getInvalidCurrenciesByPriceList($value);

        foreach (array_keys($invalidCurrencies) as $currency) {
            $this->context->addViolationAt('currencies', $constraint->message, ['%invalidCurrency%' => $currency]);
        }
    }
}
