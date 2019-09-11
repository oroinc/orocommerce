<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\CMSBundle\ContentWidget\ContentWidgetTypeInterface;
use Oro\Bundle\CMSBundle\DependencyInjection\OroCMSExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;
use Symfony\Component\DependencyInjection\ChildDefinition;

class OroCMSExtensionTest extends ExtensionTestCase
{
    public function testLoad()
    {
        $this->loadExtension(new OroCMSExtension());

        $expectedParameters = [
            'oro_cms.entity.page.class',
        ];
        $this->assertParametersLoaded($expectedParameters);

        $expectedExtensionConfigs = [
            'oro_cms',
        ];
        $this->assertExtensionConfigsLoaded($expectedExtensionConfigs);
    }

    /**
     * {@inheritdoc}
     */
    protected function getContainerMock()
    {
        $container = parent::getContainerMock();

        $childDefinition = $this->createMock(ChildDefinition::class);
        $childDefinition->expects($this->once())
            ->method('addTag')
            ->with('oro_cms.content_widget.type');

        $container->expects($this->once())
            ->method('registerForAutoconfiguration')
            ->with(ContentWidgetTypeInterface::class)
            ->willReturn($childDefinition);

        return $container;
    }
}
