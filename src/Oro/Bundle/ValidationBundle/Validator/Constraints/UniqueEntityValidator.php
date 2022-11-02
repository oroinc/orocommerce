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
    /**
     * @var ManagerRegistry
     */
    private $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @inheritDoc
     *
     * @throws ConstraintDefinitionException
     * @throws UnexpectedTypeException
     */
    public function validate($entity, Constraint $constraint)
    {
        $this->validateConstraintOptions($constraint);

        $em = $this->getEm($entity, $constraint);

        $criteria = $this->buildCriteria($entity, $constraint, $em);

        $result = $this->getResult($criteria, $entity, $constraint, $em);

        if ($this->isNoDuplicates($result, $entity)) {
            return;
        }

        if (!$constraint->buildViolationAtEntityLevel) {
            $this->buildViolationAtPath($constraint, $criteria);

            return;
        }

        $this->buildViolationAtEntityLevel($constraint);
    }

    protected function buildViolationAtPath(UniqueEntity $constraint, $criteria)
    {
        $fields = (array)$constraint->fields;

        $errorPath = null !== $constraint->errorPath ? $constraint->errorPath : $fields[0];
        $invalidValue = isset($criteria[$errorPath]) ? $criteria[$errorPath] : $criteria[$fields[0]];

        $this->context->buildViolation($constraint->message)
            ->atPath($errorPath)
            ->setInvalidValue($invalidValue)
            ->setCode(UniqueEntity::NOT_UNIQUE_ERROR)
            ->addViolation();
    }

    protected function buildViolationAtEntityLevel(UniqueEntity $constraint)
    {
        $this->context->buildViolation($constraint->message)
            ->setParameter('unique_key', implode(',', $constraint->fields))
            ->setCode(UniqueEntity::NOT_UNIQUE_ERROR)
            ->addViolation();
    }

    /**
     * @throws ConstraintDefinitionException
     */
    protected function getEm($entity, Constraint $constraint) : ObjectManager
    {
        if ($constraint->em) {
            $em = $this->registry->getManager($constraint->em);

            if (!$em) {
                throw new ConstraintDefinitionException(
                    sprintf('Object manager "%s" does not exist.', $constraint->em)
                );
            }

            return $em;
        }

        $em = $this->registry->getManagerForClass(get_class($entity));

        if (!$em) {
            throw new ConstraintDefinitionException(
                sprintf(
                    'Unable to find the object manager associated with an entity of class "%s".',
                    get_class($entity)
                )
            );
        }

        return $em;
    }

    /**
     * @throws UnexpectedTypeException
     */
    protected function validateConstraintOptions(Constraint $constraint)
    {
        if (!$constraint instanceof UniqueEntity) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__.'\UniqueEntity');
        }

        if (!is_array($constraint->fields) && !is_string($constraint->fields)) {
            throw new UnexpectedTypeException($constraint->fields, 'array');
        }

        if (null !== $constraint->errorPath && !is_string($constraint->errorPath)) {
            throw new UnexpectedTypeException($constraint->errorPath, 'string or null');
        }

        if (null !== $constraint->buildViolationAtEntityLevel && !is_bool($constraint->buildViolationAtEntityLevel)) {
            throw new UnexpectedTypeException($constraint->buildViolationAtEntityLevel, 'boolean');
        }
    }

    /**
     * @param $entity
     * @param Constraint $constraint
     * @param ObjectManager $em
     *
     * @return array
     *
     * @throws ConstraintDefinitionException
     */
    protected function buildCriteria($entity, Constraint $constraint, ObjectManager $em)
    {
        $fields = (array)$constraint->fields;

        if (0 === count($fields)) {
            throw new ConstraintDefinitionException('At least one field has to be specified.');
        }

        $class = $em->getClassMetadata(get_class($entity));

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

            if ($constraint->ignoreNull && null === $criteria[$fieldName]) {
                return [];
            }

            if (null !== $criteria[$fieldName] && $class->hasAssociation($fieldName)) {
                $em->initializeObject($criteria[$fieldName]);
            }
        }

        return $criteria;
    }

    /**
     * @param array $criteria
     * @param $entity
     * @param Constraint $constraint
     * @param ObjectManager $em
     *
     * @return array|\Traversable
     */
    protected function getResult(array $criteria, $entity, Constraint $constraint, ObjectManager $em)
    {
        $repository = $em->getRepository(get_class($entity));
        $result = $repository->{$constraint->repositoryMethod}($criteria);

        if ($result instanceof \IteratorAggregate) {
            $result = $result->getIterator();
        }

        if ($result instanceof \Iterator) {
            $result->rewind();
        } elseif (is_array($result)) {
            reset($result);
        }

        return $result;
    }

    protected function isNoDuplicates($result, $entity) : bool
    {
        if (0 === count($result) ||
            (
                1 === count($result)
                && $entity === ($result instanceof \Iterator ? $result->current() : current($result))
            )
        ) {
            return true;
        }

        return false;
    }
}
