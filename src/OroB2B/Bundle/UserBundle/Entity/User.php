<?php

namespace OroB2B\Bundle\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use FOS\UserBundle\Model\User as BaseUser;

/**
 * @ORM\Entity
 * @ORM\Table(name="orob2b_user")
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class User extends BaseUser
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToMany(targetEntity="OroB2B\Bundle\UserAdminBundle\Entity\Group")
     * @ORM\JoinTable(name="orob2b_user_group",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="group_id", referencedColumnName="id")}
     * )
     */
    protected $groups;

    /**
     * @ORM\Column(name="first_name", type="string", length=255)
     */
    protected $firstName;

    /**
     * @ORM\Column(name="last_name", type="string", length=255)
     */
    protected $lastName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $username;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $usernameCanonical;

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

    /**
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @return string
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * @param string $firstName
     * @return User
     */
    public function setFirstName($firstName)
    {
        $this->username = trim($firstName.' '.$this->getLastName());
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * @param string $lastName
     * @return User
     */
    public function setLastName($lastName)
    {
        $this->username = trim($this->getFirstName().' '.$lastName);
        $this->lastName = $lastName;

        return $this;
    }
}
