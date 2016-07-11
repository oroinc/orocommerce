<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Owner\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="tbl_account_user")
 */
class TestAccountUser
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="TestAccount", inversedBy="users")
     * @ORM\JoinColumn(name="account_id", referencedColumnName="id")
     */
    protected $account;

    /**
     * @ORM\ManyToMany(targetEntity="TestOrganization")
     * @ORM\JoinTable(name="tbl_account_user_to_organization",
     *      joinColumns={@ORM\JoinColumn(name="account_user_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="organization_id", referencedColumnName="id")}
     *  )
     */
    protected $organizations;
}
