<?php

namespace Oro\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceRuleLexeme;

/**
 *  ORM entity repository for PriceRuleLexeme entity.
 */
class PriceRuleLexemeRepository extends EntityRepository
{
    const LEXEMES_CACHE_KEY = 'oro_pricing_price_rule_lexemes_cache';

    public function deleteByPriceList(PriceList $priceList)
    {
        $qb = $this->createQueryBuilder('lexeme');

        $qb->delete()
            ->where($qb->expr()->eq('lexeme.priceList', ':priceList'))
            ->setParameter('priceList', $priceList);

        $qb->getQuery()->execute();
    }

    /**
     * @return array
     */
    public function getRelationIds()
    {
        $qb = $this->createQueryBuilder('referenceLexeme');
        $qb
            ->select('referenceLexeme.relationId')
            ->distinct()
            ->where($qb->expr()->eq('referenceLexeme.className', ':priceListClass'))
            ->andWhere($qb->expr()->isNotNull('referenceLexeme.relationId'))
            ->setParameter('priceListClass', PriceList::class);
        $result = $qb->getQuery()->getScalarResult();

        return array_map(
            function (array $value) {
                return (int)$value['relationId'];
            },
            $result
        );
    }

    /**
     * @param string $className
     * @param array|string[] $updatedFields
     * @param int|null $relationId
     * @param Organization|null $organization
     * @return array|PriceRuleLexeme[]
     */
    public function findEntityLexemes(
        string $className,
        array $updatedFields = [],
        ?int $relationId = null,
        ?Organization $organization = null
    ): array {
        $qb = $this->getLexemesQueryBuilder($className, $updatedFields, $relationId, $organization);

        return $qb->getQuery()
            ->useResultCache(true, 3600, self::LEXEMES_CACHE_KEY)
            ->getResult();
    }

    protected function getLexemesQueryBuilder(
        string $className,
        array $updatedFields = [],
        ?int $relationId = null,
        ?Organization $organization = null
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('lexeme');

        $whereExpr = $qb->expr()->andX(
            $qb->expr()->eq('lexeme.className', ':className')
        );
        $qb->setParameter('className', $className);
        if ($updatedFields) {
            $whereExpr->add($qb->expr()->in('lexeme.fieldName', ':updatedFields'));
            $qb->setParameter('updatedFields', $updatedFields);
        }
        if ($relationId) {
            $whereExpr->add($qb->expr()->eq('lexeme.relationId', ':relationId'));
            $qb->setParameter('relationId', $relationId);
        }
        if ($organization) {
            $qb->innerJoin('lexeme.priceList', 'priceList', Join::WITH);
            $whereExpr->add($qb->expr()->eq('priceList.organization', ':organization'));
            $qb->setParameter('organization', $organization);
        }

        $qb->where($whereExpr);

        return $qb;
    }

    public function invalidateCache()
    {
        $cache = $this->getEntityManager()
            ->getConfiguration()
            ->getResultCacheImpl();

        if ($cache) {
            $cache->delete(self::LEXEMES_CACHE_KEY);
        }
    }
}
