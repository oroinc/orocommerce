<?php

namespace OroB2B\Bundle\PaymentBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;

/**
 * @ORM\Table(name="orob2b_payment_term")
 * @ORM\Entity(repositoryClass="OroB2B\Bundle\PaymentBundle\Entity\Repository\PaymentTermRepository")
 * @Config(
 *      routeName="orob2b_payment_term_index",
 *      routeView="orob2b_payment_term_view",
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-usd"
 *          },
 *          "dataaudit"={
 *              "auditable"=true
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"=""
 *          },
 *          "form"={
 *              "form_type"="orob2b_payment_term_select",
 *              "grid_name"="payment-terms-select-grid",
 *          }
 *      }
 * )
 */
class PaymentTerm
{
    /**
     * @var integer
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(name="label", type="string")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $label;

    /**
     * @var AccountGroup[]|Collection
     *
     * @ORM\ManyToMany(targetEntity="OroB2B\Bundle\AccountBundle\Entity\AccountGroup")
     * @ORM\JoinTable(
     *      name="orob2b_payment_term_to_acc_grp",
     *      joinColumns={
     *          @ORM\JoinColumn(name="payment_term_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="account_group_id", referencedColumnName="id", onDelete="CASCADE", unique=true)
     *      }
     * )
     */
    protected $accountGroups;

    /**
     * @var Account[]|Collection
     *
     * @ORM\ManyToMany(targetEntity="OroB2B\Bundle\AccountBundle\Entity\Account")
     * @ORM\JoinTable(
     *      name="orob2b_payment_term_to_account",
     *      joinColumns={
     *          @ORM\JoinColumn(name="payment_term_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="account_id", referencedColumnName="id", onDelete="CASCADE", unique=true)
     *      }
     * )
     */
    protected $accounts;

    public function __construct()
    {
        $this->accountGroups = new ArrayCollection();
        $this->accounts = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->label;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set label
     *
     * @param string $label
     * @return $this
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @param AccountGroup $accountGroup
     *
     * @return PaymentTerm
     */
    public function addAccountGroup(AccountGroup $accountGroup)
    {
        if (!$this->accountGroups->contains($accountGroup)) {
            $this->accountGroups->add($accountGroup);
        }

        return $this;
    }

    /**
     * @param AccountGroup $accountGroup
     *
     * @return PaymentTerm
     */
    public function removeAccountGroup(AccountGroup $accountGroup)
    {
        if ($this->accountGroups->contains($accountGroup)) {
            $this->accountGroups->removeElement($accountGroup);
        }

        return $this;
    }

    /**
     * Get account groups
     *
     * @return Collection|AccountGroup[]
     */
    public function getAccountGroups()
    {
        return $this->accountGroups;
    }

    /**
     * @param Account $account
     *
     * @return PaymentTerm
     */
    public function addAccount(Account $account)
    {
        if (!$this->accounts->contains($account)) {
            $this->accounts->add($account);
        }

        return $this;
    }

    /**
     * @param Account $account
     *
     * @return PaymentTerm
     */
    public function removeAccount(Account $account)
    {
        if ($this->accounts->contains($account)) {
            $this->accounts->removeElement($account);
        }

        return $this;
    }

    /**
     * Get account groups
     *
     * @return Collection|Account[]
     */
    public function getAccounts()
    {
        return $this->accounts;
    }
}
