<?php

namespace Oro\Bundle\DPDBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\EntityBundle\EntityProperty\CreatedAtAwareTrait;
use Oro\Bundle\OrderBundle\Entity\Order;
use JMS\Serializer\Annotation as JMS;

/**
 * @ORM\Table(name="oro_dpd_shipping_transaction")
 * @ORM\Entity
 */
class DPDTransaction
{
    use CreatedAtAwareTrait;

    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(type="string", length=36)
     * @ORM\GeneratedValue(strategy="UUID")
     */
    protected $id;

    /**
     * @var array
     *
     * @ORM\Column(name="parcel_numbers", type="array")
     */
    protected $parcelNumbers = [];


    /**
     * @var Order
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrderBundle\Entity\Order")
     * @ORM\JoinColumn(name="order_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $order;

    /**
     * @var File
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\AttachmentBundle\Entity\File", cascade={"persist"})
     * @ORM\JoinColumn(name="file_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     * @JMS\Exclude
     */
    protected $labelFile;

    /**
     * DPDTransaction constructor.
     */
    public function __construct()
    {
        $this->parcelNumbers = array();
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $parcelNumber
     *
     * @return DPDTransaction
     */
    public function addParcelNumber($parcelNumber)
    {
        $this->parcelNumbers[] = $parcelNumber;

        return $this;
    }

    /**
     * @param array $parcelNumbers
     *
     * @return DPDTransaction
     */
    public function setParcelNumbers(array $parcelNumbers)
    {
        $this->parcelNumbers = $parcelNumbers;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getParcelNumbers()
    {
        return $this->parcelNumbers;
    }

    /**
     * @return Order
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @param Order $order
     * @return DPDTransaction
     */
    public function setOrder(Order $order)
    {
        $this->order = $order;
        return $this;
    }

    /**
     * @return File
     */
    public function getLabelFile()
    {
        return $this->labelFile;
    }

    /**
     * @param File $labelFile
     * @return DPDTransaction
     */
    public function setLabelFile(File $labelFile = null)
    {
        $this->labelFile = $labelFile;
        return $this;
    }
}
