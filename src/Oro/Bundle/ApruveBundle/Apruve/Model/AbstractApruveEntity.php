<?php

namespace Oro\Bundle\ApruveBundle\Apruve\Model;

use Symfony\Component\HttpFoundation\ParameterBag;

abstract class AbstractApruveEntity extends ParameterBag
{
    /**
     *{@inheritdoc}
     */
    public function toArray()
    {
        return $this->all();
    }
}
