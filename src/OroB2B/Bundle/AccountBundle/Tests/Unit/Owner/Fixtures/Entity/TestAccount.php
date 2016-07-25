<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Owner\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="tbl_account")
 */
class TestAccount
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="TestAccount", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
     */
    protected $parent;

    /**
     * @ORM\OneToMany(targetEntity="TestAccount", mappedBy="parent")
     */
    protected $children;

    /**
     * @ORM\OneToMany(targetEntity="TestAccountUser", mappedBy="account")
     */
    protected $users;

    /**
     * @ORM\ManyToOne(targetEntity="TestOrganization")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id")
     */
    protected $organization;
}
