<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\CMSBundle\DependencyInjection\Compiler\ExtendFieldValidationLoaderPass;
use Oro\Bundle\CMSBundle\Validator\Constraints\TwigContent;
use Oro\Bundle\CMSBundle\Validator\Constraints\WYSIWYG;
use Oro\Bundle\CMSBundle\Validator\Constraints\WYSIWYGStyle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ExtendFieldValidationLoaderPassTest extends \PHPUnit\Framework\TestCase
{
    private ExtendFieldValidationLoaderPass $compiler;

    protected function setUp(): void
    {
        $this->compiler = new ExtendFieldValidationLoaderPass();
    }

    public function testProcessWithoutTargetServices(): void
    {
        $container = new ContainerBuilder();

        $this->compiler->process($container);
    }

    public function testProcess(): void
    {
        $container = new ContainerBuilder();
        $validationLoaderDef = $container->register('oro_entity_extend.validation_loader');
        $validatorDef = $container->register('oro_serialized_fields.validator.extend_entity_serialized_data');

        $this->compiler->process($container);

        self::assertEquals(
            [
                ['addConstraints', ['wysiwyg', [[TwigContent::class => null], [WYSIWYG::class => null]]]],
                ['addConstraints', ['wysiwyg_style', [[TwigContent::class => null], [WYSIWYGStyle::class => null]]]],
            ],
            $validationLoaderDef->getMethodCalls()
        );
        self::assertEquals(
            [
                ['addConstraints', ['wysiwyg', [[TwigContent::class => null], [WYSIWYG::class => null]]]],
                ['addConstraints', ['wysiwyg_style', [[TwigContent::class => null], [WYSIWYGStyle::class => null]]]],
            ],
            $validatorDef->getMethodCalls()
        );
    }
}
