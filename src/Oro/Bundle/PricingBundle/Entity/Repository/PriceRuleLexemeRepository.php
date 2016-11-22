<?php

namespace Oro\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceRuleLexeme;

class PriceRuleLexemeRepository extends EntityRepository
{
    /**
     * @param PriceList $priceList
     */
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
     * @param array $updatedFields
     * @param null|int $relationId
     * @return array|PriceRuleLexeme[]
     */
    public function findEntityLexemes($className, array $updatedFields = [], $relationId = null)
    {
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

        $qb->where($whereExpr);

        return $qb->getQuery()->getResult();
    }
}
