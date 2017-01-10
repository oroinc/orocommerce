<?php

namespace Oro\Bundle\CustomerBundle\Entity\Ownership;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\CustomerBundle\Entity\Customer;

trait AuditableFrontendAccountAwareTrait
{
    /**
     * @var Customer
     *
     * @ORM\ManyToOne(
     *      targetEntity="Oro\Bundle\CustomerBundle\Entity\Customer",
     *      cascade={"persist"}
     * )
     * @ORM\JoinColumn(name="customer_id", referencedColumnName="id", onDelete="SET NULL")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
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
