<?php

namespace OroB2B\Bundle\AccountBundle\Entity;

use Doctrine\Common\Collections\Collection;
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
     * @var AuditField[]|Collection
     *
     * @ORM\OneToMany(
     *      targetEntity="OroB2B\Bundle\AccountBundle\Entity\AuditField",
     *      mappedBy="audit",
     *      cascade={"persist"}
     * )
     */
    protected $fields;

    /**
     * {@inheritdoc}
     */
    protected function getAuditFieldInstance(AbstractAudit $audit, $field, $dataType, $newValue, $oldValue)
    {
        return new AuditField($audit, $field, $dataType, $newValue, $oldValue);
    }

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
