<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\PromotionBundle\DependencyInjection\Compiler\PromotionContextConverterCompilerPass;
use Oro\Component\DependencyInjection\Tests\Unit\Compiler\TaggedServicesCompilerPassCase;

class PromotionContextConverterCompilerPassTest extends TaggedServicesCompilerPassCase
{
    public function testProcess()
    {
        $this->assertTaggedServicesRegistered(
            new PromotionContextConverterCompilerPass(),
            PromotionContextConverterCompilerPass::REGISTRY,
            PromotionContextConverterCompilerPass::TAG,
            'registerConverter'
        );
    }
}
