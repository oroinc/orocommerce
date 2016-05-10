<?php

namespace OroB2B\Bundle\AccountBundle\Entity\Ownership;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use OroB2B\Bundle\AccountBundle\Entity\Account;

trait AuditableFrontendAccountAwareTrait
{
    /**
     * @var Account
     *
     * @ORM\ManyToOne(
     *      targetEntity="OroB2B\Bundle\AccountBundle\Entity\Account",
     *      cascade={"persist"}
     * )
     * @ORM\JoinColumn(name="account_id", referencedColumnName="id", onDelete="SET NULL")
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
     * @return Account|null
     */
    public function getAccount()
    {
        return $this->account;
    }

    /**
     * @param Account|null $account
     * @return $this
     */
    public function setAccount(Account $account = null)
    {
        $this->account = $account;

        return $this;
    }
}
