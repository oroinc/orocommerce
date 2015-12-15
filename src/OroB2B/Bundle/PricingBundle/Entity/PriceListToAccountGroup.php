<?php

namespace OroB2B\Bundle\PricingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;

/**
 * @ORM\Table(name="orob2b_price_list_to_acc_group")
 * @ORM\Entity(repositoryClass="OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListToAccountGroupRepository")
 */
class PriceListToAccountGroup extends BasePriceListRelation
{
    /**
     * @var AccountGroup
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\AccountBundle\Entity\AccountGroup")
     * @ORM\JoinColumn(name="account_group_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $accountGroup;

    /**
     * @return AccountGroup
     */
    public function getAccountGroup()
    {
        return $this->accountGroup;
    }

    /**
     * @param AccountGroup $accountGroup
     * @return $this
     */
    public function setAccountGroup(AccountGroup $accountGroup)
    {
        $this->accountGroup = $accountGroup;

        return $this;
    }
}
