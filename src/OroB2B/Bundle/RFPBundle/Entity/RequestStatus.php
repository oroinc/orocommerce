<?php

namespace OroB2B\Bundle\RFPBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use OroB2B\Bundle\RFPBundle\Model\AbstractRequestStatus;

/**
 * RequestStatus
 *
 * @ORM\Table(
 *      name="orob2b_rfp_status",
 *      indexes={
 *          @ORM\Index(name="orob2b_rfp_status_name_idx",columns={"name"})
 *      }
 * )
 * @ORM\Entity
 */
class RequestStatus extends AbstractRequestStatus
{
}
