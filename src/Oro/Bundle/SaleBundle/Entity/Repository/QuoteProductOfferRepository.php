<?php

namespace Oro\Bundle\SaleBundle\Entity\Repository;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;

/**
 * Doctrine repository for QuoteProductOffer entity
 */
class QuoteProductOfferRepository extends EntityRepository
{
    /**
     * @return array<int,ArrayCollection<QuoteProductOffer>>
     */
    public function getProductOffersByQuoteIds(array $quoteProductIds): array
    {
        $queryBuilder = $this->createQueryBuilder('items');

        $queryBuilder->select('items')
            ->where($queryBuilder->expr()->in('items.quoteProduct', ':quoteProductIds'))
            ->setParameter('quoteProductIds', $quoteProductIds, Connection::PARAM_INT_ARRAY);

        $result = [];
        /** @var QuoteProductOffer $item */
        foreach ($queryBuilder->getQuery()->toIterable() as $item) {
            $id = $item->getQuoteProduct()->getId();
            if (!array_key_exists($id, $result)) {
                $result[$id] = new ArrayCollection();
            }
            $result[$id]->add($item);
        }

        return $result;
    }
}
