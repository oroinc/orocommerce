<?php

namespace Oro\Bundle\SaleBundle\Entity\Repository;

use Doctrine\DBAL\Exception\DriverException;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\CustomerBundle\Entity\Repository\ResetCustomerUserTrait;
use Oro\Bundle\CustomerBundle\Entity\Repository\ResettableCustomerUserRepositoryInterface;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SaleBundle\Entity\Quote;

/**
 * Doctrine repository for Quote entity.
 */
class QuoteRepository extends EntityRepository implements ResettableCustomerUserRepositoryInterface
{
    use ResetCustomerUserTrait;

    private function getQueryBuildertoFetchQuote(): QueryBuilder
    {
        return $this->createQueryBuilder('q')
            ->select(['q', 'quoteProducts', 'quoteProductOffers'])
            ->leftJoin('q.quoteProducts', 'quoteProducts')
            ->leftJoin('quoteProducts.quoteProductOffers', 'quoteProductOffers');
    }

    /**
     * @param int $id
     * @return Quote|null
     */
    public function getQuote($id): ?Quote
    {
        $qb = $this->getQueryBuildertoFetchQuote();
        $qb->where($qb->expr()->eq('q.id', ':id'))
            ->setParameter('id', (int)$id);

        try {
            return $qb
                ->getQuery()
                ->getSingleResult();
        } catch (NoResultException|NonUniqueResultException $e) {
        }

        return null;
    }

    public function getQuoteByGuestAccessId(string $guestAccessId): ?Quote
    {
        $qb = $this->getQueryBuildertoFetchQuote();
        $qb->where($qb->expr()->eq('q.guestAccessId', ':guestAccessId'))
            ->setParameter('guestAccessId', $guestAccessId);

        try {
            return $qb
                ->getQuery()
                ->getSingleResult();
        } catch (NoResultException|NonUniqueResultException|DriverException $e) {
        }

        return null;
    }

    public function hasRecordsWithRemovingCurrencies(
        array $removingCurrencies,
        Organization $organization = null
    ): bool {
        $qb = $this->createQueryBuilder('q');
        $qb
            ->select('COUNT(q.id)')
            ->leftJoin('q.quoteProducts', 'quoteProducts')
            ->leftJoin('quoteProducts.quoteProductOffers', 'quoteProductOffers')
            ->where($qb->expr()->in('quoteProductOffers.currency', ':removingCurrencies'))
            ->setParameter('removingCurrencies', $removingCurrencies);
        if ($organization instanceof Organization) {
            $qb->andWhere('q.organization = :organization');
            $qb->setParameter(':organization', $organization);
        }

        return (bool) $qb->getQuery()->getSingleScalarResult();
    }
}
