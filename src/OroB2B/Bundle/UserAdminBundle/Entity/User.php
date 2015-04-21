<?php

namespace OroB2B\Bundle\UserAdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use FOS\UserBundle\Entity\User as BaseUser;

/**
 * @ORM\Entity
 * @ORM\Table(name="orob2b_user")
 */
class User extends BaseUser
{
    use UserTrait;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $email;

    /**
     * @ORM\Column(type="string", length=255, name="email_canonical", unique=true)
     */
    protected $emailCanonical;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $enabled;

    /**
     * @ORM\Column(type="string")
     */
    protected $salt;

    /**
     * @ORM\Column(type="string")
     */
    protected $password;

    /**
     * Plain password. Used for model validation. Must not be persisted.
     *
     * @var string
     */
    protected $plainPassword;

    /**
     * @ORM\Column(type="datetime", nullable=true, name="last_login")
     */
    protected $lastLogin;

    /**
     * @ORM\Column(type="string", nullable=true, name="confirmation_token")
     */
    protected $confirmationToken;

    /**
     * @ORM\Column(type="datetime", nullable=true, name="password_requested_at")
     */
    protected $passwordRequestedAt;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $locked;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $expired;

    /**
     * @ORM\Column(type="datetime", nullable=true, name="expires_at")
     */
    protected $expiresAt;

    /**
     * @ORM\Column(type="array")
     */
    protected $roles;

    /**
     * @ORM\Column(type="boolean", name="credentials_expired")
     */
    protected $credentialsExpired;

    /**
     * @ORM\Column(type="datetime", nullable=true, name="credentials_expire_at")
     */
    protected $credentialsExpireAt;
}
