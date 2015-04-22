<?php

namespace OroB2B\Bundle\RFPBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use OroB2B\Bundle\RFPBundle\Model\AbstractRequest;

/**
 * Request
 *
 * @ORM\Table("orob2b_rfp_request")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 */
class Request extends AbstractRequest
{
    /**
     * @var RequestStatus
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\RFPBundle\Entity\RequestStatus")
     * @ORM\JoinColumn(name="status_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $status;
}
