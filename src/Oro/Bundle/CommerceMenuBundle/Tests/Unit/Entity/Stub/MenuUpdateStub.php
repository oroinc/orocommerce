<?php

namespace Oro\Bundle\CommerceMenuBundle\Tests\Unit\Entity\Stub;

use Oro\Bundle\CommerceMenuBundle\Entity\MenuUpdate;

class MenuUpdateStub extends MenuUpdate
{
    /**
     * @var mixed
     */
    protected $image;

    /**
     * @return mixed
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @param $image
     * @return MenuUpdateStub
     */
    public function setImage($image)
    {
        $this->image = $image;

        return $this;
    }
}
