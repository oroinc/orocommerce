<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\CMSBundle\ContentWidget\ContentWidgetTypeInterface;
use Oro\Bundle\CMSBundle\DependencyInjection\OroCMSExtension;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroCMSExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var OroCMSExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->extension = new OroCMSExtension();
    }

    public function testConfigureContentWidgetType()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        $contentWidgetTypeAutoconfigurationDefinition =  (new ChildDefinition(''))
            ->addTag('oro_cms.content_widget.type');

        $this->extension->load([], $container);
        self::assertEquals(
            [
                ContentWidgetTypeInterface::class => $contentWidgetTypeAutoconfigurationDefinition
            ],
            $container->getAutoconfiguredInstanceof()
        );
    }
}
