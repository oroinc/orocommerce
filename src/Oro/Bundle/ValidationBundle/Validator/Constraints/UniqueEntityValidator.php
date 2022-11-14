<?php

namespace Oro\Bundle\ValidationBundle\Validator\Constraints;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * This validator is basically a copy of @see \Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntityValidator,
 * but this validator has an additional option 'buildViolationAtEntityLevel' that allows to not build violations at
 * some property path. This had to be another class, because Doctrine's UniqueEntityValidator was written poorly,
 * without possibility to extend.
 */
class UniqueEntityValidator extends ConstraintValidator
{
    private ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * {@inheritDoc}
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueEntity) {
            throw new UnexpectedTypeException($constraint, UniqueEntity::class);
        }

        if (!\is_array($constraint->fields) && !\is_string($constraint->fields)) {
            throw new UnexpectedTypeException($constraint->fields, 'array');
        }

        if (null !== $constraint->errorPath && !\is_string($constraint->errorPath)) {
            throw new UnexpectedTypeException($constraint->errorPath, 'string or null');
        }

        $em = $this->getEntityManager($value, $constraint);
        $criteria = $this->buildCriteria($value, $constraint, $em);
        $result = $this->getResult($criteria, $value, $constraint, $em);
        if ($this->isNoDuplicates($result, $value)) {
            return;
        }

        if ($constraint->buildViolationAtEntityLevel) {
            $this->buildViolationAtEntityLevel($constraint);
        } else {
            $this->buildViolationAtPath($constraint, $criteria);
        }
    }

    private function buildViolationAtPath(UniqueEntity $constraint, array $criteria): void
    {
        $fields = (array)$constraint->fields;
        $errorPath = $constraint->errorPath ?? $fields[0];

        $this->context->buildViolation($constraint->message)
            ->atPath($errorPath)
            ->setInvalidValue($criteria[$errorPath] ?? $criteria[$fields[0]])
            ->setCode(UniqueEntity::NOT_UNIQUE_ERROR)
            ->addViolation();
    }

    private function buildViolationAtEntityLevel(UniqueEntity $constraint): void
    {
        $this->context->buildViolation($constraint->message)
            ->setParameter('unique_key', implode(',', $constraint->fields))
            ->setCode(UniqueEntity::NOT_UNIQUE_ERROR)
            ->addViolation();
    }

    private function getEntityManager(object $entity, UniqueEntity $constraint) : ObjectManager
    {
        if ($constraint->em) {
            $em = $this->doctrine->getManager($constraint->em);
            if (!$em) {
                throw new ConstraintDefinitionException(sprintf(
                    'Object manager "%s" does not exist.',
                    $constraint->em
                ));
            }

            return $em;
        }

        $em = $this->doctrine->getManagerForClass(\get_class($entity));
        if (!$em) {
            throw new ConstraintDefinitionException(sprintf(
                'Unable to find the object manager associated with an entity of class "%s".',
                \get_class($entity)
            ));
        }

        return $em;
    }

    private function buildCriteria(object $entity, UniqueEntity $constraint, ObjectManager $em): array
    {
        $fields = (array)$constraint->fields;
        if (0 === \count($fields)) {
            throw new ConstraintDefinitionException('At least one field has to be specified.');
        }

        $class = $em->getClassMetadata(\get_class($entity));

        $criteria = [];
        foreach ($fields as $fieldName) {
            if (!$class->hasField($fieldName) && !$class->hasAssociation($fieldName)) {
                throw new ConstraintDefinitionException(sprintf(
                    'The field "%s" is not mapped by Doctrine, so it cannot be validated for uniqueness.',
                    $fieldName
                ));
            }

            $criteria[$fieldName] = $class->reflFields[$fieldName]->getValue($entity);

            if ($constraint->ignoreNull && null === $criteria[$fieldName]) {
                return [];
            }

            if (null !== $criteria[$fieldName] && $class->hasAssociation($fieldName)) {
                $em->initializeObject($criteria[$fieldName]);
            }
        }

        return $criteria;
    }

    private function getResult(array $criteria, object $entity, Constraint $constraint, ObjectManager $em): mixed
    {
        $repository = $em->getRepository(\get_class($entity));
        $result = $repository->{$constraint->repositoryMethod}($criteria);

        if ($result instanceof \IteratorAggregate) {
            $result = $result->getIterator();
        }

        if ($result instanceof \Iterator) {
            $result->rewind();
        } elseif (\is_array($result)) {
            reset($result);
        }

        return $result;
    }

    private function isNoDuplicates(mixed $result, object $entity) : bool
    {
        return
            0 === \count($result)
            || (
                1 === \count($result)
                && $entity === ($result instanceof \Iterator ? $result->current() : current($result))
            );
    }
}
