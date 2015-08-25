<?php

namespace OroB2B\Bundle\AccountBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\UserBundle\Entity\AbstractRole;

use OroB2B\Bundle\WebsiteBundle\Entity\Website;

/**
 * @ORM\Entity(repositoryClass="OroB2B\Bundle\AccountBundle\Entity\Repository\AccountUserRoleRepository")
 * @ORM\Table(name="orob2b_account_user_role")
 * @Config(
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-briefcase"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"="commerce"
 *          },
 *          "dataaudit"={
 *              "auditable"=true
 *          },
 *          "activity"={
 *              "show_on_page"="\Oro\Bundle\ActivityBundle\EntityConfig\ActivityScope::UPDATE_PAGE"
 *          }
 *      }
 * )
 */
class AccountUserRole extends AbstractRole
{
    const PREFIX_ROLE = 'ROLE_FRONTEND_';

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer", name="id")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=64, unique=true, nullable=false)
     */
    protected $role;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=64)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          },
     *      }
     * )
     */
    protected $label;

    /**
     * @var Website[]|Collection
     *
     * @ORM\ManyToMany(targetEntity="OroB2B\Bundle\WebsiteBundle\Entity\Website")
     * @ORM\JoinTable(
     *      name="orob2b_account_role_to_website",
     *      joinColumns={
     *          @ORM\JoinColumn(name="account_user_role_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="website_id", referencedColumnName="id", onDelete="CASCADE", unique=true)
     *      }
     * )
     */
    protected $websites;

    /**
     * @param string|null $role
     */
    public function __construct($role = null)
    {
        if ($role) {
            $this->setRole($role, false);
        }

        $this->websites = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string $label
     * @return AccountUserRole
     */
    public function setLabel($label)
    {
        $this->label = (string)$label;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPrefix()
    {
        return static::PREFIX_ROLE;
    }

    /**
     * @param Website $website
     * @return $this
     */
    public function addWebsite(Website $website)
    {
        if (!$this->websites->contains($website)) {
            $this->websites->add($website);
        }

        return $this;
    }

    /**
     * @param Website $website
     * @return $this
     */
    public function removeWebsite(Website $website)
    {
        if ($this->websites->contains($website)) {
            $this->websites->removeElement($website);
        }

        return $this;
    }

    /**
     * @return Collection|Website[]
     */
    public function getWebsites()
    {
        return $this->websites;
    }
}
