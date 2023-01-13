<?php

namespace Oro\Bundle\PricingBundle\Validator\Constraints;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Entity\BasePriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\BasePriceListRepository;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates that a price list does not have invalid currencies.
 */
class PriceListProductPricesCurrencyValidator extends ConstraintValidator
{
    private ShardManager $shardManager;
    private ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine, ShardManager $shardManager)
    {
        $this->doctrine = $doctrine;
        $this->shardManager = $shardManager;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof PriceListProductPricesCurrency) {
            throw new UnexpectedTypeException($constraint, PriceListProductPricesCurrency::class);
        }

        if (!$value instanceof BasePriceList) {
            throw new UnexpectedTypeException($value, BasePriceList::class);
        }

        $invalidCurrencies = $this->getPriceListRepository(ClassUtils::getClass($value))
            ->getInvalidCurrenciesByPriceList($this->shardManager, $value);
        foreach ($invalidCurrencies as $currency) {
            $this->context->buildViolation($constraint->message, ['%invalidCurrency%' => $currency])
                ->atPath('currencies')
                ->addViolation();
        }
    }

    private function getPriceListRepository(string $priceListEntityClass): BasePriceListRepository
    {
        return $this->doctrine->getRepository($priceListEntityClass);
    }
}
