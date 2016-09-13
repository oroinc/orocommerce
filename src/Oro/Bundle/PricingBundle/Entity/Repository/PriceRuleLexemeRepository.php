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
     * @param PriceList $priceList
     * @param string $fieldName
     * @param bool $isRuleLexeme
     * @return array|PriceRuleLexeme[]
     */
    public function getAffectedPriceRuleLexemes(PriceList $priceList, $fieldName, $isRuleLexeme)
    {
        $qb = $this->createQueryBuilder('lexeme');
        
        $qb->where($qb->expr()->eq('lexeme.className', ':priceListClass'))
            ->andWhere($qb->expr()->eq('lexeme.fieldName', ':fieldName'))
            ->andWhere($qb->expr()->eq('lexeme.relationId', ':relationId'))
            ->setParameters([
                'priceListClass' => PriceList::class,
                'fieldName' => $fieldName,
                'relationId' => $priceList->getId()
            ]);
        
        if ($isRuleLexeme) {
            $qb->andWhere($qb->expr()->isNotNull('lexeme.priceRule'));
        } else {
            $qb->andWhere($qb->expr()->isNull('lexeme.priceRule'));
        }
        
        return $qb->getQuery()->getResult();
    }
}
