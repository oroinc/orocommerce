<?php

namespace OroB2B\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList;
use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceListToAccount;
use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceListToAccountGroup;
use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceListToPriceList;
use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceListToWebsite;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class CombinedPriceListRepository extends EntityRepository
{
    /**
     * @param CombinedPriceList $combinedPriceList
     * @return CombinedPriceListToPriceList[]
     */
    public function getPriceListRelations(CombinedPriceList $combinedPriceList)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('partial cpl.{id, priceList, mergeAllowed}')
            ->from('OroB2BPricingBundle:CombinedPriceListToPriceList', 'cpl')
            ->where($qb->expr()->eq('cpl.combinedPriceList', ':combinedPriceList'))
            ->setParameter('combinedPriceList', $combinedPriceList)
            ->orderBy('cpl.sortOrder');

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Account $account
     * @param Website $website
     * @param bool|true $isEnabled
     * @return null|CombinedPriceList
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getCombinedPriceListByAccount(Account $account, Website $website, $isEnabled = true)
    {
        $qb = $this->createQueryBuilder('priceList');
        $qb
            ->innerJoin(
                'OroB2BPricingBundle:CombinedPriceListToAccount',
                'priceListToAccount',
                Join::WITH,
                'priceListToAccount.priceList = priceList'
            )
            ->where($qb->expr()->eq('priceListToAccount.account', ':account'))
            ->andWhere($qb->expr()->eq('priceListToAccount.website', ':website'))
            ->andWhere($qb->expr()->eq('priceList.enabled', ':enabled'))
            ->setParameter('account', $account)
            ->setParameter('website', $website)
            ->setParameter('enabled', $isEnabled)
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }


    /**
     * @param AccountGroup $accountGroup
     * @param Website $website
     * @param bool|true $isEnabled
     * @return null|CombinedPriceList
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getCombinedPriceListByAccountGroup(AccountGroup $accountGroup, Website $website, $isEnabled = true)
    {
        $qb = $this->createQueryBuilder('priceList');
        $qb
            ->innerJoin(
                'OroB2BPricingBundle:CombinedPriceListToAccountGroup',
                'priceListToAccountGroup',
                Join::WITH,
                'priceListToAccountGroup.priceList = priceList'
            )
            ->where($qb->expr()->eq('priceListToAccountGroup.accountGroup', ':accountGroup'))
            ->andWhere($qb->expr()->eq('priceListToAccountGroup.website', ':website'))
            ->andWhere($qb->expr()->eq('priceList.enabled', ':enabled'))
            ->setParameter('accountGroup', $accountGroup)
            ->setParameter('website', $website)
            ->setParameter('enabled', $isEnabled)
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param Website $website
     * @param bool|true $isEnabled
     * @return CombinedPriceList|null
     */
    public function getCombinedPriceListByWebsite(Website $website, $isEnabled = true)
    {
        $qb = $this->createQueryBuilder('priceList');

        $qb
            ->innerJoin(
                'OroB2BPricingBundle:CombinedPriceListToWebsite',
                'priceListToWebsite',
                Join::WITH,
                'priceListToWebsite.priceList = priceList'
            )
            ->where($qb->expr()->eq('priceListToWebsite.website', ':website'))
            ->andWhere($qb->expr()->eq('priceList.enabled', ':enabled'))
            ->setParameter('website', $website)
            ->setParameter('enabled', $isEnabled)
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param array CombinedPriceList[] $exceptPriceLists
     * @param bool|null $priceListsEnabled
     */
    public function deleteUnusedPriceLists(array $exceptPriceLists = [], $priceListsEnabled = true)
    {
        $iterator = $this->getUnusedPriceListsIterator($exceptPriceLists, $priceListsEnabled);
        $bufferSize = $this->getBufferSize();
        $iterator->setBufferSize($bufferSize);

        $deleteQb = $this->getEntityManager()->createQueryBuilder()
            ->delete($this->getEntityName(), 'cplDelete');

        $deleteQb->where($deleteQb->expr()->in('cplDelete.id', ':unusedPriceLists'));

        $priceListsIdForDelete = [];
        $i = 0;
        foreach ($iterator as $priceList) {
            $priceListsIdForDelete[] = $priceList->getId();
            $i++;
            if ($i === $bufferSize) {
                $deleteQb->setParameter('unusedPriceLists', $priceListsIdForDelete)
                    ->getQuery()->execute();
                $priceListsIdForDelete = [];
                $i = 0;
            }
        }
        if ($priceListsIdForDelete) {
            $deleteQb->setParameter('unusedPriceLists', $priceListsIdForDelete)
                ->getQuery()->execute();
        }
    }

    /**
     * @return int
     */
    protected function getBufferSize()
    {
        return BufferedQueryResultIterator::DEFAULT_BUFFER_SIZE;
    }

    /**
     * @param array $exceptPriceLists
     * @param bool|null $priceListsEnabled
     * @return BufferedQueryResultIterator
     */
    protected function getUnusedPriceListsIterator(array $exceptPriceLists = [], $priceListsEnabled = true)
    {
        $selectQb = $this->createQueryBuilder('priceList')
            ->select('priceList');

        $relations = [
            'priceListToWebsite' => 'OroB2BPricingBundle:CombinedPriceListToWebsite',
            'priceListToAccountGroup' => 'OroB2BPricingBundle:CombinedPriceListToAccountGroup',
            'priceListToAccount' => 'OroB2BPricingBundle:CombinedPriceListToAccount',
        ];

        foreach ($relations as $alias => $entityName) {
            $selectQb->leftJoin(
                $entityName,
                $alias,
                Join::WITH,
                $selectQb->expr()->eq($alias . '.priceList', 'priceList.id')
            );
            $selectQb->andWhere($alias . '.priceList IS NULL');
        }
        if ($exceptPriceLists) {
            $selectQb->andWhere($selectQb->expr()->notIn('priceList', ':exceptPriceLists'))
                ->setParameter('exceptPriceLists', $exceptPriceLists);
        }
        if ($priceListsEnabled !== null) {
            $selectQb->andWhere($selectQb->expr()->eq('priceList.enabled', ':isEnabled'))
                ->setParameter('isEnabled', $priceListsEnabled);
        }

        return new BufferedQueryResultIterator($selectQb->getQuery());
    }

    /**
     * @param CombinedPriceList $combinedPriceList
     * @param Account|AccountGroup|Website $targetEntity
     */
    public function updateCombinedPriceListConnection(CombinedPriceList $combinedPriceList, $targetEntity)
    {
        $em = $this->getEntityManager();
        $relation = null;
        if ($targetEntity instanceof Account) {
            $relation = $em->getRepository('OroB2BPricingBundle:CombinedPriceListToAccount')
                ->findOneBy(['account' => $targetEntity]);
            if (!$relation) {
                $relation = new CombinedPriceListToAccount();
                $relation->setAccount($targetEntity);
                $em->persist($relation);
            }
        } elseif ($targetEntity instanceof AccountGroup) {
            $relation = $em->getRepository('OroB2BPricingBundle:CombinedPriceListToAccountGroup')
                ->findOneBy(['account' => $targetEntity]);
            if (!$relation) {
                $relation = new CombinedPriceListToAccountGroup();
                $relation->setAccountGroup($targetEntity);
                $em->persist($relation);
            }
        } elseif ($targetEntity instanceof Website) {
            $relation = $em->getRepository('OroB2BPricingBundle:CombinedPriceListToWebsite')
                ->findOneBy(['account' => $targetEntity]);
            if (!$relation) {
                $relation = new CombinedPriceListToWebsite();
                $relation->setWebsite($targetEntity);
                $em->persist($relation);
            }
        } else {
            throw new \InvalidArgumentException(sprintf('Unknown target "%s"', get_class($targetEntity)));
        }

        if (!$relation->getPriceList() || $relation->getPriceList()->getId() !== $combinedPriceList->getId()) {
            $relation->setPriceList($combinedPriceList);
            $em->flush($relation);
        }
    }
}
