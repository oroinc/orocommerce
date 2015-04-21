<?php

namespace OroB2B\Bundle\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use FOS\UserBundle\Entity\User as BaseUser;
use OroB2B\Bundle\UserAdminBundle\Entity\UserTrait;

/**
 * @ORM\Entity
 * @ORM\Table(name="orob2b_user")
 */
class User extends BaseUser
{
    use UserTrait;
}
