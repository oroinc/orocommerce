<?php

namespace Oro\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Entity\AccountGroup;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListChangeTrigger;

class PriceListChangeTriggerRepository extends EntityRepository
{
    /**
     * @return BufferedQueryResultIterator|PriceListChangeTrigger[]
     */
    public function getPriceListChangeTriggersIterator()
    {
        $qb = $this->createQueryBuilder('changes');

        return new BufferedQueryResultIterator($qb->getQuery());
    }

    public function deleteAll()
    {
        $this->createQueryBuilder('priceListChangeTrigger')
        ->delete('OroPricingBundle:PriceListChangeTrigger', 'priceListChangeTrigger')
        ->getQuery()
        ->execute();
    }

    /**
     * @param Website[] $websites
     * @param AccountGroup[] $accountGroups
     * @param Account[] $accounts
     */
    public function clearExistingScopesPriceListChangeTriggers(
        array $websites = [],
        array $accountGroups = [],
        array $accounts = []
    ) {
        $qb = $this->_em->createQueryBuilder();

        $qb->delete('OroPricingBundle:PriceListChangeTrigger', 'priceListChangeTrigger');

        if ($websites) {
            $qb->andWhere($qb->expr()->in('priceListChangeTrigger.website', ':websites'))
                ->setParameter('websites', $websites);
        }
        if ($accountGroups) {
            $qb->andWhere($qb->expr()->in('priceListChangeTrigger.accountGroup', ':accountGroups'))
                ->setParameter('accountGroups', $accountGroups);
        }
        if ($accounts) {
            $qb->andWhere($qb->expr()->in('priceListChangeTrigger.account', ':accounts'))
                ->setParameter('accounts', $accounts);
        }

        $qb->getQuery()->execute();
    }

    /**
     * @param PriceList $priceList
     * @param InsertFromSelectQueryExecutor $insertFromSelect
     */
    public function generateAccountsTriggersByPriceList(
        PriceList $priceList,
        InsertFromSelectQueryExecutor $insertFromSelect
    ) {
        $qb =  $this->getEntityManager()
            ->getRepository('OroPricingBundle:PriceListToAccount')
            ->createQueryBuilder('priceListToAccount');

        $qb->select([
            'IDENTITY(priceListToAccount.account)',
            'IDENTITY(account.group)',
            'IDENTITY(priceListToAccount.website)'
        ])
        ->leftJoin('priceListToAccount.account', 'account')
        ->where('priceListToAccount.priceList = :priceList')
        ->setParameter('priceList', $priceList);

        $insertFromSelect->execute(
            $this->getEntityName(),
            ['account', 'accountGroup', 'website'],
            $qb
        );
    }

    /**
     * @param PriceList $priceList
     * @param InsertFromSelectQueryExecutor $insertFromSelect
     */
    public function generateAccountGroupsTriggersByPriceList(
        PriceList $priceList,
        InsertFromSelectQueryExecutor $insertFromSelect
    ) {
        $qb =  $this->getEntityManager()
            ->getRepository('OroPricingBundle:PriceListToAccountGroup')
            ->createQueryBuilder('PriceListToAccountGroup');

        $qb->select([
            'IDENTITY(PriceListToAccountGroup.accountGroup)',
            'IDENTITY(PriceListToAccountGroup.website)'
        ])
        ->where('PriceListToAccountGroup.priceList = :priceList')
        ->setParameter('priceList', $priceList);

        $insertFromSelect->execute(
            $this->getEntityName(),
            ['accountGroup', 'website'],
            $qb
        );
    }

    /**
     * @param PriceList $priceList
     * @param InsertFromSelectQueryExecutor $insertFromSelect
     */
    public function generateWebsitesTriggersByPriceList(
        PriceList $priceList,
        InsertFromSelectQueryExecutor $insertFromSelect
    ) {
        $qb =  $this->getEntityManager()
            ->getRepository('OroPricingBundle:PriceListToWebsite')
            ->createQueryBuilder('priceListToWebsite');

        $qb->select([
            'IDENTITY(priceListToWebsite.website)'
        ])
        ->where('priceListToWebsite.priceList = :priceList')
        ->setParameter('priceList', $priceList);

        $insertFromSelect->execute(
            $this->getEntityName(),
            ['website'],
            $qb
        );
    }

    /**
     * @return PriceListChangeTrigger
     */
    public function findBuildAllForceTrigger()
    {
        return $this->findOneBy([
                'account' => null,
                'accountGroup' => null,
                'website' => null,
                'force' => true
            ]);
    }
}
