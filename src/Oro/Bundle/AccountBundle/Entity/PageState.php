<?php

namespace Oro\Bundle\AccountBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\NavigationBundle\Entity\AbstractPageState;
use Oro\Bundle\UserBundle\Entity\AbstractUser;

/**
 * Page state entity
 *
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="oro_acc_pagestate")
 */
class PageState extends AbstractPageState
{
    /**
     * @var AbstractUser $user
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\AccountBundle\Entity\AccountUser")
     * @ORM\JoinColumn(name="account_user_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $user;
}
