<?php

namespace Oro\Bundle\PricingBundle\Validator\Constraints;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;

class UniqueEntityValidator extends ConstraintValidator
{
    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var ShardManager
     */
    private $shardManager;

    /**
     * UniqueEntityValidator constructor.
     */
    public function __construct(ManagerRegistry $registry, ShardManager $shardManager)
    {
        $this->registry = $registry;
        $this->shardManager = $shardManager;
    }

    /**
     * @param UniqueEntity $constraint
     * {@inheritdoc}
     */
    public function validate($entity, Constraint $constraint)
    {
        if (!$entity instanceof ProductPrice) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Value must be instance of "%s", "%s" given',
                    'Oro\Bundle\PricingBundle\Entity\ProductPrice',
                    is_object($entity) ? ClassUtils::getClass($entity) : gettype($entity)
                )
            );
        }
        if (!$this->isObjectCanBeValidated($entity)) {
            return;
        }
        /** @var EntityManager $em */
        $em = $this->registry->getManager();
        $criteria = [];
        $fields = $constraint->fields;
        $this->getCriteria($em, $entity, $criteria, $fields);
        $priceList = $entity->getPriceList();
        $result = $em
            ->getRepository(ProductPrice::class)
            ->findByPriceList(
                $this->shardManager,
                $priceList,
                $criteria
            );

        $countResult = count($result);
        if (0 === $countResult || (1 === $countResult && $entity === current($result))) {
            return;
        }
        /** @var ExecutionContext $context */
        $context = $this->context;
        $context->buildViolation($constraint->message)->addViolation();
    }

    private function getCriteria(EntityManager $em, ProductPrice $entity, array &$criteria, array $fields)
    {
        /* @var ClassMetadata $class */
        $class = $em->getClassMetadata(ProductPrice::class);

        foreach ($fields as $fieldName) {
            if (!$class->hasField($fieldName) && !$class->hasAssociation($fieldName)) {
                throw new ConstraintDefinitionException(
                    sprintf(
                        'The field "%s" is not mapped by Doctrine, so it cannot be validated for uniqueness.',
                        $fieldName
                    )
                );
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

    /**
     * @param ProductPrice $entity
     *
     * @return bool
     */
    private function isObjectCanBeValidated(ProductPrice $entity)
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
