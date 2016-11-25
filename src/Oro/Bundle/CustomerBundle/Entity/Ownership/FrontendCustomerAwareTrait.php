<?php

namespace Oro\Bundle\CustomerBundle\Entity\Ownership;

use Oro\Bundle\CustomerBundle\Entity\Account;

trait FrontendCustomerAwareTrait
{
    /**
     * @var Account
     *
     * @ORM\ManyToOne(
     *      targetEntity="Oro\Bundle\CustomerBundle\Entity\Account",
     *      cascade={"persist"}
     * )
     * @ORM\JoinColumn(name="account_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $account;

    /**
     * @return Account|null
     */
    public function getCustomer()
    {
        return $this->account;
    }

    /**
     * @param Account|null $customer
     * @return $this
     */
    public function setCustomer(Account $customer = null)
    {
        $this->account = $customer;

        return $this;
    }
}
