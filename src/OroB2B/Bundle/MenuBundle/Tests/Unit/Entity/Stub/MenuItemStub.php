<?php

namespace OroB2B\Bundle\MenuBundle\Tests\Unit\Entity\Stub;

use Oro\Bundle\AttachmentBundle\Entity\File;

use OroB2B\Bundle\MenuBundle\Entity\MenuItem;

class MenuItemStub extends MenuItem
{
    /**
     * @var File
     */
    protected $image;

    /**
     * @return File[]
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @param File[] $image
     * @return $this
     */
    public function setImage(array $image)
    {
        $this->image = $image;

        return $this;
    }
}
