<?php

namespace OroB2B\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use OroB2B\Bundle\CustomerBundle\Entity\Customer;
use OroB2B\Bundle\CustomerBundle\Entity\CustomerGroup;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class PriceListRepository extends EntityRepository
{
    public function dropDefaults()
    {
        $qb = $this->createQueryBuilder('pl');

        $qb
            ->update()
            ->set('pl.default', ':defaultValue')
            ->setParameter('defaultValue', false)
            ->where($qb->expr()->eq('pl.default', ':oldValue'))
            ->setParameter('oldValue', true)
            ->getQuery()
            ->execute();
    }

    /**
     * @param PriceList $priceList
     */
    public function setDefault(PriceList $priceList)
    {
        $this->dropDefaults();

        $qb = $this->createQueryBuilder('pl');

        $qb
            ->update()
            ->set('pl.default', ':newValue')
            ->setParameter('newValue', true)
            ->where($qb->expr()->eq('pl', ':entity'))
            ->setParameter('entity', $priceList)
            ->getQuery()
            ->execute();
    }

    /**
     * @return PriceList
     */
    public function getDefault()
    {
        $qb = $this->createQueryBuilder('pl');

        return $qb
            ->where($qb->expr()->eq('pl.default', ':default'))
            ->setParameter('default', true)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param Customer $customer
     * @return PriceList|null
     */
    public function getPriceListByCustomer(Customer $customer)
    {
        return $this->createQueryBuilder('priceList')
            ->innerJoin('priceList.customers', 'customer')
            ->andWhere('customer = :customer')
            ->setParameter('customer', $customer)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param Customer $customer
     * @param PriceList $priceList
     */
    public function setPriceListToCustomer(Customer $customer, PriceList $priceList = null)
    {
        $oldPriceList = $this->getPriceListByCustomer($customer);

        if ($oldPriceList && $priceList && $oldPriceList->getId() === $priceList->getId()) {
            return;
        }

        if ($oldPriceList) {
            $oldPriceList->removeCustomer($customer);
        }

        if ($priceList) {
            $priceList->addCustomer($customer);
        }
    }

    /**
     * @param CustomerGroup $customerGroup
     * @return PriceList|null
     */
    public function getPriceListByCustomerGroup(CustomerGroup $customerGroup)
    {
        return $this->createQueryBuilder('priceList')
            ->innerJoin('priceList.customerGroups', 'customerGroup')
            ->andWhere('customerGroup = :customerGroup')
            ->setParameter('customerGroup', $customerGroup)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param CustomerGroup $customerGroup
     * @param PriceList $priceList
     */
    public function setPriceListToCustomerGroup(CustomerGroup $customerGroup, PriceList $priceList = null)
    {
        $oldPriceList = $this->getPriceListByCustomerGroup($customerGroup);

        if ($oldPriceList && $priceList && $oldPriceList->getId() === $priceList->getId()) {
            return;
        }

        if ($oldPriceList) {
            $oldPriceList->removeCustomerGroup($customerGroup);
        }

        if ($priceList) {
            $priceList->addCustomerGroup($customerGroup);
        }
    }

    /**
     * @param Website $website
     * @return PriceList|null
     */
    public function getPriceListByWebsite(Website $website)
    {
        return $this->createQueryBuilder('priceList')
            ->innerJoin('priceList.websites', 'website')
            ->andWhere('website = :website')
            ->setParameter('website', $website)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param Website $website
     * @param PriceList $priceList
     */
    public function setPriceListToWebsite(Website $website, PriceList $priceList = null)
    {
        $oldPriceList = $this->getPriceListByWebsite($website);

        if ($oldPriceList && $priceList && $oldPriceList->getId() === $priceList->getId()) {
            return;
        }

        if ($oldPriceList) {
            $oldPriceList->removeWebsite($website);
        }

        if ($priceList) {
            $priceList->addWebsite($website);
        }
    }
}
