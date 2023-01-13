<?php

namespace Oro\Bundle\TaxBundle\Validator\Constraints;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\TaxBundle\Entity\AbstractTaxCode;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * This validator is used to check whether a tax code is unique.
 */
class UniqueTaxCodeValidator extends ConstraintValidator
{
    private ManagerRegistry $doctrine;
    private AclHelper $aclHelper;

    public function __construct(ManagerRegistry $doctrine, AclHelper $aclHelper)
    {
        $this->doctrine = $doctrine;
        $this->aclHelper = $aclHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueTaxCode) {
            throw new UnexpectedTypeException($constraint, UniqueTaxCode::class);
        }

        if (null === $value) {
            return;
        }

        if (!$value instanceof AbstractTaxCode) {
            throw new UnexpectedTypeException($value, AbstractTaxCode::class);
        }

        if (!$value->getCode()) {
            return;
        }

        if ($this->isTaxCodeExists($value)) {
            $this->context->buildViolation($constraint->message)
                ->atPath('code')
                ->setInvalidValue($value->getCode())
                ->addViolation();
        }
    }

    protected function isTaxCodeExists(AbstractTaxCode $taxCode): bool
    {
        $rows = $this->aclHelper->apply($this->getTaxCodeIdsQueryBuilder($taxCode))->getArrayResult();

        return \count($rows) > 0;
    }

    protected function getTaxCodeIdsQueryBuilder(AbstractTaxCode $taxCode): QueryBuilder
    {
        $qb = $this->getRepository(\get_class($taxCode))
            ->createQueryBuilder('e')
            ->select('e.id')
            ->where('e.code = :code')
            ->setParameter('code', $taxCode->getCode());
        if ($taxCode->getId() !== null) {
            $qb->andWhere('e.id != :id')
                ->setParameter('id', $taxCode->getId());
        }

        return $qb;
    }

    private function getRepository(string $taxCodeEntityClass): EntityRepository
    {
        return $this->doctrine->getRepository($taxCodeEntityClass);
    }
}
