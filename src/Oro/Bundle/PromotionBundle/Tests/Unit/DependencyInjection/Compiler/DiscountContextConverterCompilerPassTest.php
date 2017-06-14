<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\PromotionBundle\DependencyInjection\Compiler\DiscountContextConverterCompilerPass;
use Oro\Component\DependencyInjection\Tests\Unit\Compiler\TaggedServicesCompilerPassCase;

class DiscountContextConverterCompilerPassTest extends TaggedServicesCompilerPassCase
{
    public function testProcess()
    {
        $this->assertTaggedServicesRegistered(
            new DiscountContextConverterCompilerPass(),
            DiscountContextConverterCompilerPass::REGISTRY,
            DiscountContextConverterCompilerPass::TAG,
            'registerConverter'
        );
    }
}
