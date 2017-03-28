<?php

namespace Oro\Bundle\PricingBundle\Validator\Constraints;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Util\ClassUtils;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;

use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;

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
     * @param ManagerRegistry $registry
     * @param ShardManager $shardManager
     */
    public function __construct(ManagerRegistry $registry, ShardManager $shardManager)
    {
        $this->registry = $registry;
        $this->shardManager = $shardManager;
    }

    /**
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
        $em = $this->registry->getManager();

        /* @var $class ClassMetadata */
        $class =  $em->getClassMetadata(ProductPrice::class);
        $fields = $constraint->fields;
        $criteria = [];
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

        $priceList = $entity->getPriceList();
        $result = $em
            ->getRepository(ProductPrice::class)
            ->findByPriceList(
                $this->shardManager,
                $priceList,
                $criteria
            );

        if (0 === count($result) || (1 === count($result) && $entity === current($result))) {
            return;
        }

        $this->context->buildViolation($constraint->message)->addViolation();
    }
}
