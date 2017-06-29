<?php

namespace Oro\Bundle\ProductBundle\Provider;

trait ProductNewArrivalStickerTrait
{
    /**
     * @return array
     */
    protected function getNewArrivalSticker()
    {
        return ['type' => 'new_arrival'];
    }
}
