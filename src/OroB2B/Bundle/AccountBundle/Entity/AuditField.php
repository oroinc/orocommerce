<?php

namespace OroB2B\Bundle\AccountBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\DataAuditBundle\Entity\AbstractAuditField;

/**
 * @ORM\Entity
 * @ORM\Table(name="orob2b_audit_field")
 */
class AuditField extends AbstractAuditField
{
    /**
     * @var Audit
     *
     * @ORM\ManyToOne(
     *      targetEntity="OroB2B\Bundle\AccountBundle\Entity\Audit",
     *      inversedBy="fields",
     *      cascade={"persist"}
     * )
     * @ORM\JoinColumn(name="audit_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $audit;
}
