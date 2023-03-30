<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Provider\ProductKitsByUnitPrecisionProvider;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Checks if {@see ProductUnitPrecision::$unit} can be changed taking into account product kit items referencing it.
 */
class ProductUnitPrecisionReferencedByProductKitItemsValidator extends ConstraintValidator
{
    private ManagerRegistry $managerRegistry;

    private ProductKitsByUnitPrecisionProvider $productKitsByUnitPrecisionProvider;

    public function __construct(
        ManagerRegistry $managerRegistry,
        ProductKitsByUnitPrecisionProvider $productKitsByUnitPrecisionProvider
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->productKitsByUnitPrecisionProvider = $productKitsByUnitPrecisionProvider;
    }

    /**
     * @param ProductUnitPrecision|null $value
     * @param ProductUnitPrecisionsCollectionReferencedByProductKitItems $constraint
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof ProductUnitPrecisionReferencedByProductKitItems) {
            throw new UnexpectedTypeException($constraint, ProductUnitPrecisionReferencedByProductKitItems::class);
        }

        if (!$value instanceof ProductUnitPrecision) {
            throw new UnexpectedValueException($value, ProductUnitPrecision::class);
        }

        /** @var EntityManager $entityManager */
        $entityManager = $this->managerRegistry->getManagerForClass(ProductUnitPrecision::class);
        $unitOfWork = $entityManager->getUnitOfWork();
        $originalData = $unitOfWork->getOriginalEntityData($value);
        if (!$originalData || $originalData['unit']->getCode() === $value->getProductUnitCode()) {
            // Skips further execution as the product unit is not changed.
            return;
        }

        $productsSkus = $this->productKitsByUnitPrecisionProvider->getRelatedProductKitsSku($value);
        if (!$productsSkus) {
            // Skips further execution as the product unit precision is not referenced by product kit items.
            return;
        }

        $this->context
            ->buildViolation($constraint->message)
            ->setParameter('{{ product_unit }}', $value->getProductUnitCode())
            ->setParameter('{{ product_kits_skus }}', implode(', ', $productsSkus))
            ->atPath('unit')
            ->setCode(ProductUnitPrecisionReferencedByProductKitItems::UNIT_PRECISION_CANNOT_BE_CHANGED)
            ->addViolation();
    }
}
