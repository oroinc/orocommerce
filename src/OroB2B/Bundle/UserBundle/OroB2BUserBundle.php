<?php

namespace OroB2B\Bundle\UserBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroB2BUserBundle extends Bundle
{
    public function getParent()
    {
        return 'FOSUserBundle';
    }
}
