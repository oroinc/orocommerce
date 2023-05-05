<?php

namespace Oro\Bundle\PricingBundle\Validator\Constraints;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * This validator checks whether a product does not have duplication of product prices.
 */
class UniqueEntityValidator extends ConstraintValidator
{
    private ManagerRegistry $doctrine;
    private ShardManager $shardManager;

    public function __construct(ManagerRegistry $doctrine, ShardManager $shardManager)
    {
        $this->doctrine = $doctrine;
        $this->shardManager = $shardManager;
    }

    /**
     * {@inheritDoc}
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueEntity) {
            throw new UnexpectedTypeException($value, UniqueEntity::class);
        }

        if (!$value instanceof ProductPrice) {
            throw new UnexpectedTypeException($value, ProductPrice::class);
        }

        if (!$this->isObjectCanBeValidated($value)) {
            return;
        }

        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManager();
        $criteria = [];
        $this->getCriteria($em, $value, $criteria, $constraint->fields);
        $result = $em->getRepository(ProductPrice::class)->findByPriceList(
            $this->shardManager,
            $value->getPriceList(),
            $criteria
        );
        $countResult = \count($result);
        if (0 === $countResult || (1 === $countResult && $value === current($result))) {
            return;
        }
        $this->context->buildViolation($constraint->message)->addViolation();
    }

    private function getCriteria(
        EntityManagerInterface $em,
        ProductPrice $entity,
        array &$criteria,
        array $fields
    ): void {
        $class = $em->getClassMetadata(ProductPrice::class);
        foreach ($fields as $fieldName) {
            if (!$class->hasField($fieldName) && !$class->hasAssociation($fieldName)) {
                throw new ConstraintDefinitionException(sprintf(
                    'The field "%s" is not mapped by Doctrine, so it cannot be validated for uniqueness.',
                    $fieldName
                ));
            }
            $criteria[$fieldName] = $class->reflFields[$fieldName]->getValue($entity);
            if (null === $criteria[$fieldName]) {
                return;
            }
            if (null !== $criteria[$fieldName] && $class->hasAssociation($fieldName)) {
                $em->initializeObject($criteria[$fieldName]);
            }
        }
    }

    private function isObjectCanBeValidated(ProductPrice $entity): bool
    {
        if ($entity->getProduct() && null === $entity->getProduct()->getId()) {
            // for new product prices can't exist in db
            return false;
        }
        if ($entity->getPriceList() === null || $entity->getPriceList()->getId() === null) {
            return false;
        }

        return true;
    }
}
