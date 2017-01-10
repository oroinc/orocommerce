<?php

namespace Oro\Bundle\PricingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\CustomerBundle\Entity\Customer;

/**
 * @ORM\Table(name="oro_price_list_to_customer")
 * @ORM\Entity(repositoryClass="Oro\Bundle\PricingBundle\Entity\Repository\PriceListToAccountRepository")
 */
class PriceListToAccount extends BasePriceListRelation
{
    /**
     * @var Customer
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\CustomerBundle\Entity\Customer")
     * @ORM\JoinColumn(name="customer_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $account;

    /**
     * @return Customer
     */
    public function getAccount()
    {
        return $this->account;
    }

    /**
     * @param Customer $account
     * @return $this
     */
    public function setAccount(Customer $account)
    {
        $this->account = $account;

        return $this;
    }
}
