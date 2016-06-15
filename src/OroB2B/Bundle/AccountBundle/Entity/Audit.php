<?php

namespace OroB2B\Bundle\AccountBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\DataAuditBundle\Entity\AbstractAudit;
use Oro\Bundle\UserBundle\Entity\AbstractUser;

/**
 * @ORM\Entity()
 */
class Audit extends AbstractAudit
{
    /**
     * @var AccountUser $user
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\AccountBundle\Entity\AccountUser", cascade={"persist"})
     * @ORM\JoinColumn(name="account_user_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     */
    protected $accountUser;
    
    /**
     * {@inheritdoc}
     */
    public function setUser(AbstractUser $user = null)
    {
        $this->accountUser = $user;
    }

    /**
     * {@inheritdoc}
     */
    public function getUser()
    {
        return $this->accountUser;
    }
}
