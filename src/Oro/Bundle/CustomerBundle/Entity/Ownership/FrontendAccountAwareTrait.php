<?php

namespace Oro\Bundle\CustomerBundle\Entity\Ownership;

use Oro\Bundle\CustomerBundle\Entity\Customer;

/**
 * @deprecated Use FrontendCustomerAwareTrait
 */
trait FrontendAccountAwareTrait
{
    /**
     * @var Customer
     *
     * @ORM\ManyToOne(
     *      targetEntity="Oro\Bundle\CustomerBundle\Entity\Customer",
     *      cascade={"persist"}
     * )
     * @ORM\JoinColumn(name="customer_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $account;

    /**
     * @return Customer|null
     */
    public function getAccount()
    {
        return $this->account;
    }

    /**
     * @param Customer|null $account
     * @return $this
     */
    public function setAccount(Customer $account = null)
    {
        $this->account = $account;

        return $this;
    }
}
