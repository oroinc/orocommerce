<?php

namespace Oro\Bundle\PricingBundle\Validator\Constraints;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Util\ClassUtils;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

use Oro\Bundle\PricingBundle\Entity\BasePriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\BasePriceListRepository;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;

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
        if (!$value instanceof BasePriceList) {
            throw new UnexpectedTypeException($value, BasePriceList::class);
        }

        $class = ClassUtils::getClass($value);
        /** @var BasePriceListRepository $repository */
        $repository = $this->registry->getManagerForClass($class)
            ->getRepository($class);
        $invalidCurrencies = $repository->getInvalidCurrenciesByPriceList($value);

        /** @var ExecutionContextInterface $context */
        $context = $this->context;
        foreach ($invalidCurrencies as $currency) {
            $context->buildViolation($constraint->message, ['%invalidCurrency%' => $currency])
                ->atPath('currencies')
                ->addViolation();
        }
    }
}
