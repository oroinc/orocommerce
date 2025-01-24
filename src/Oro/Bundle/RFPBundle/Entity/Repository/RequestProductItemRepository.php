<?php

namespace Oro\Bundle\RFPBundle\Entity\Repository;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\RFPBundle\Entity\RequestProductItem;

/**
 * Doctrine repository for RequestProductItem entity
 */
class RequestProductItemRepository extends EntityRepository
{
    /**
     * @return array<int,ArrayCollection<RequestProductItem>>
     */
    public function getProductItemsByRequestIds(array $requestProductIds): array
    {
        $queryBuilder = $this->createQueryBuilder('items');

        $queryBuilder->select('items')
            ->where($queryBuilder->expr()->in('items.requestProduct', ':requestProductIds'))
            ->setParameter('requestProductIds', $requestProductIds, Connection::PARAM_INT_ARRAY);

        $result = [];
        /** @var RequestProductItem $item */
        foreach ($queryBuilder->getQuery()->toIterable() as $item) {
            $id = $item->getRequestProduct()->getId();
            if (!array_key_exists($id, $result)) {
                $result[$id] = new ArrayCollection();
            }
            $result[$id]->add($item);
        }

        return $result;
    }
}
