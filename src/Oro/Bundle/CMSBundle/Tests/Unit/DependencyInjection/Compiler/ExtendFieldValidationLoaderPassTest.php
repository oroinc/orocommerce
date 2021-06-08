<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\CMSBundle\DependencyInjection\Compiler\ExtendFieldValidationLoaderPass;
use Oro\Bundle\CMSBundle\Validator\Constraints\TwigContent;
use Oro\Bundle\CMSBundle\Validator\Constraints\WYSIWYG;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ExtendFieldValidationLoaderPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var ExtendFieldValidationLoaderPass */
    private $compiler;

    protected function setUp(): void
    {
        $this->compiler = new ExtendFieldValidationLoaderPass();
    }

    public function testProcessWithoutTargerServices(): void
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

        $this->assertEquals(
            [
                ['addConstraints', ['wysiwyg', [[TwigContent::class => null], [WYSIWYG::class => null]]]]
            ],
            $validationLoaderDef->getMethodCalls()
        );
        $this->assertEquals(
            [
                ['addConstraints', ['wysiwyg', [[TwigContent::class => null], [WYSIWYG::class => null]]]]
            ],
            $validatorDef->getMethodCalls()
        );
    }
}
