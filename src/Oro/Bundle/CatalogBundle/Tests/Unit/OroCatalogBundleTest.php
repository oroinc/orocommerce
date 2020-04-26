<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit;

use Oro\Bundle\CatalogBundle\DependencyInjection\CompilerPass\AttributeBlockTypeMapperPass;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\OroCatalogBundle;
use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\DefaultFallbackExtensionPass;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class OroCatalogBundleTest extends \PHPUnit\Framework\TestCase
{
    public function testBuild()
    {
        /** @var ContainerBuilder|MockObject $containerBuilder */
        $containerBuilder = $this->getMockBuilder(ContainerBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addCompilerPass'])
            ->getMock();

        $containerBuilder->expects(static::exactly(2))
            ->method('addCompilerPass')
            ->withConsecutive(
                [
                    static::logicalAnd(
                        static::isInstanceOf(DefaultFallbackExtensionPass::class),
                        static::callback(function (CompilerPassInterface $compilerPass) {
                            $generatorExtensionDef = $this->getMockBuilder(Definition::class)
                                ->disableOriginalConstructor()
                                ->getMock();
                            $generatorExtensionDef->expects(static::once())
                                ->method('getArgument')
                                ->willReturn([]);
                            /** @var ContainerBuilder|MockObject $container */
                            $container = $this->getMockBuilder(ContainerBuilder::class)
                                ->disableOriginalConstructor()
                                ->getMock();
                            $container->expects(static::once())
                                ->method('getDefinition')
                                ->with('oro_locale.entity_generator.extension')
                                ->willReturn($generatorExtensionDef);

                            $generatorExtensionDef->expects(static::once())
                                ->method('setArgument')
                                ->with(0, [
                                    Category::class => [
                                        'title' => 'titles',
                                        'shortDescription' => 'shortDescriptions',
                                        'longDescription' => 'longDescriptions',
                                        'slugPrototype' => 'slugPrototypes'
                                    ]
                                ]);

                            $compilerPass->process($container);

                            return true;
                        })
                    )
                ],
                [
                    static::isInstanceOf(AttributeBlockTypeMapperPass::class)
                ]
            )
        ->willReturnSelf();

        (new OroCatalogBundle())->build($containerBuilder);
    }
}
