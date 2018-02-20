<?php

namespace Oro\Bundle\PricingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CustomerBundle\Entity\Customer;

/**
 * @ORM\Table(
 *      name="oro_cmb_price_list_to_cus",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(name="oro_cpl_to_cus_ws_unq", columns={
 *              "customer_id",
 *              "website_id"
 *          })
 *      }
 * )
 * @ORM\Entity(repositoryClass="Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToCustomerRepository")
 */
class CombinedPriceListToCustomer extends BaseCombinedPriceListRelation
{
    /**
     * @var Customer
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\CustomerBundle\Entity\Customer")
     * @ORM\JoinColumn(name="customer_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    protected $customer;

    /**
     * @return Customer
     */
    public function getCustomer()
    {
        return $this->customer;
    }

    /**
     * @param Customer $customer
     * @return $this
     */
    public function setCustomer(Customer $customer)
    {
        $this->customer = $customer;

        return $this;
    }
}
