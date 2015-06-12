<?php

namespace OroB2B\Bundle\CustomerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\UserBundle\Entity\AbstractRole;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

/**
 * @ORM\Entity
 * @ORM\Table(name="orob2b_account_user_role")
 * @Config(
 *      routeName="orob2b_customer_account_user_role_index",
 *      defaultValues={
 *          "security"={
 *              "type"="ACL",
 *              "group_name"=""
 *          },
 *          "dataaudit"={
 *              "auditable"=true
 *          },
 *          "note"={
 *              "immutable"=true
 *          },
 *          "activity"={
 *              "immutable"=true
 *          },
 *          "attachment"={
 *              "immutable"=true
 *          }
 *      }
 * )
 */
class AccountUserRole extends AbstractRole
{
    const ROLE_PREFIX = 'ROLE_';

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
     * @ORM\Column(type="string", unique=true, length=64, nullable=false)
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
     * Constructor is empty to override obligatory argument in parent
     */
    public function __construct()
    {
    }

    /**
     * Return the role id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Return the role name field
     *
     * @return string
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * Return the role label field
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set the new label for role
     *
     * @param  string $label New label
     * @return $this
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
        return static::ROLE_PREFIX;
    }
}
