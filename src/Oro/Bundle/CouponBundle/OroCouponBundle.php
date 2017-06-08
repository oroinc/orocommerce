<?php

namespace Oro\Bundle\CouponBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\CouponBundle\DependencyInjection\OroCouponBundleExtension;

class OroCouponBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        if (!$this->extension) {
            $this->extension = new OroCouponBundleExtension();
        }

        return $this->extension;
    }
}
