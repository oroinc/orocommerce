<?php

namespace OroB2B\Bundle\RFPBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

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
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=255, nullable=true)
     */
    protected $label;

    /**
     * @var integer
     *
     * @ORM\Column(name="sort_order", type="integer", nullable=true)
     */
    protected $sortOrder;

    /**
     * @var boolean
     *
     * @ORM\Column(name="deleted", type="boolean", options={"default"=false})
     */
    protected $deleted = false;
}
