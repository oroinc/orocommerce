<?php

namespace Oro\Bundle\PricingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\CustomerBundle\Entity\Customer;

/**
 * @ORM\Table(
 *      name="oro_price_list_cus_fb",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(name="oro_price_list_cus_fb_unq", columns={
 *              "customer_id",
 *              "website_id"
 *          })
 *      }
 * )
 * @ORM\Entity(repositoryClass="Oro\Bundle\PricingBundle\Entity\Repository\PriceListAccountFallbackRepository")
 */
class PriceListAccountFallback extends PriceListFallback
{
    const ACCOUNT_GROUP = 0;
    const CURRENT_ACCOUNT_ONLY = 1;

    /** @var Customer
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\CustomerBundle\Entity\Customer")
     * @ORM\JoinColumn(name="customer_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
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
     *
     * @return $this
     */
    public function setAccount($account)
    {
        $this->account = $account;

        return $this;
    }
}
