<?php

namespace OroB2B\Bundle\CustomerBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\DataAuditBundle\Entity\AbstractAudit;
use Oro\Bundle\UserBundle\Entity\AbstractUser;

/**
 * @ORM\Entity()
 * @ORM\Table(name="orob2b_audit", indexes={
 *      @ORM\Index(name="idx_orob2b_audit_logged_at", columns={"logged_at"})
 * })
 */
class Audit extends AbstractAudit
{
    /**
     * @var AccountUser $user
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\CustomerBundle\Entity\AccountUser", cascade={"persist"})
     * @ORM\JoinColumn(name="account_user_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $accountUser;

    /**
     * @var AuditField[]|Collection
     *
     * @ORM\OneToMany(
     *      targetEntity="OroB2B\Bundle\CustomerBundle\Entity\AuditField",
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
