<?php

namespace Oro\Bundle\CustomerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\DataAuditBundle\Entity\AbstractAudit;
use Oro\Bundle\UserBundle\Entity\AbstractUser;

/**
 * @ORM\Entity()
 */
class Audit extends AbstractAudit
{
    /**
     * @var CustomerUser $user
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\CustomerBundle\Entity\CustomerUser", cascade={"persist"})
     * @ORM\JoinColumn(name="customer_user_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     */
    protected $customerUser;

    /**
     * {@inheritdoc}
     */
    public function setUser(AbstractUser $user = null)
    {
        $this->customerUser = $user;
    }

    /**
     * {@inheritdoc}
     */
    public function getUser()
    {
        return $this->customerUser;
    }
}
