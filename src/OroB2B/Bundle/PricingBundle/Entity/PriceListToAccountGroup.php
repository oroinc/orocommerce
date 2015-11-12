<?php

namespace OroB2B\Bundle\PricingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;

/**
 * @ORM\Table(name="orob2b_price_list_to_c_group")
 * @ORM\Entity(repositoryClass="OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListToAccountGroupRepository")
 */
class PriceListToAccountGroup extends AbstractPriceListRelation
{
    /**
     * @var AccountGroup
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\AccountBundle\Entity\AccountGroup")
     * @ORM\JoinColumn(name="account_group_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $account;
}
