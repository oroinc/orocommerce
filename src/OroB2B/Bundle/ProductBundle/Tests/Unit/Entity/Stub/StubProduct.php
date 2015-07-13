<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Entity\Stub;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;

use OroB2B\Bundle\ProductBundle\Entity\Product;

class StubProduct extends Product
{
    /**
     * @var AbstractEnumValue
     */
    private $inventoryStatus;

    /**
     * @var mixed
     */
    private $visibility = [];

    /**
     * @var mixed
     */
    private $image = [];

    /**
     * @var AbstractEnumValue
     */
    private $status;

    /**
     * @return AbstractEnumValue
     */
    public function getVisibility()
    {
        return $this->visibility;
    }

    /**
     * @param mixed $visibility
     * @return AbstractEnumValue
     */
    public function setVisibility($visibility)
    {
        $this->visibility = $visibility;

        return $this;
    }

    /**
     * @return AbstractEnumValue
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param AbstractEnumValue $status
     * @return $this
     */
    public function setStatus(AbstractEnumValue $status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return File
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @param File $image
     * @return File
     */
    public function setImage($image)
    {
        $this->image = $image;

        return $this;
    }

    /**
     * @return AbstractEnumValue
     */
    public function getInventoryStatus()
    {
        return $this->inventoryStatus;
    }

    /**
     * @param AbstractEnumValue $inventoryStatus
     * @return $this
     */
    public function setInventoryStatus(AbstractEnumValue $inventoryStatus)
    {
        $this->inventoryStatus = $inventoryStatus;

        return $this;
    }
}
