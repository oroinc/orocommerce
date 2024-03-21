<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\CMSBundle\ContentWidget\ContentWidgetTypeInterface;
use Oro\Bundle\CMSBundle\DependencyInjection\Configuration;
use Oro\Bundle\CMSBundle\DependencyInjection\OroCMSExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroCMSExtensionTest extends TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        $extension = new OroCMSExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());
        self::assertSame(
            [
                [
                    'settings' => [
                        'resolved' => true,
                        Configuration::DIRECT_URL_PREFIX => ['value' => '', 'scope' => 'app'],
                        Configuration::HOME_PAGE => [
                            'value' => null,
                            'scope' => 'app'
                        ]
                    ]
                ]
            ],
            $container->getExtensionConfig('oro_cms')
        );

        self::assertFalse($container->getParameter('oro_cms.direct_editing.login_page_css_field'));
        self::assertSame('default', $container->getParameter('oro_cms.content_restrictions_mode'));
        self::assertSame([], $container->getParameter('oro_cms.lax_content_restrictions'));

        self::assertEquals(
            [
                ContentWidgetTypeInterface::class => (new ChildDefinition(''))->addTag('oro_cms.content_widget.type')
            ],
            $container->getAutoconfiguredInstanceof()
        );
    }
}
