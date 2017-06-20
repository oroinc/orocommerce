<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\PromotionBundle\DependencyInjection\Compiler\PromotionCompilerPass;
use Oro\Component\DependencyInjection\Tests\Unit\Compiler\TaggedServicesCompilerPassCase;

class PromotionCompilerPassTest extends TaggedServicesCompilerPassCase
{
    public function testProcess()
    {
        $compilerPass = new PromotionCompilerPass();

        $this->assertTaggedServicesRegistered(
            $compilerPass,
            [
                PromotionCompilerPass::DISCOUNT_CONTEXT_CONVERTER_REGISTRY,
                PromotionCompilerPass::PROMOTION_CONTEXT_DATA_CONVERTER_REGISTRY,
                PromotionCompilerPass::DISCOUNT_STRATEGY_REGISTRY

            ],
            [
                PromotionCompilerPass::DISCOUNT_CONTEXT_CONVERTER_TAG,
                PromotionCompilerPass::PROMOTION_CONTEXT_DATA_CONVERTER_TAG,
                PromotionCompilerPass::DISCOUNT_STRATEGY_TAG
            ],
            [
                'registerConverter',
                'registerConverter',
                'addStrategy'
            ]
        );
    }
}
