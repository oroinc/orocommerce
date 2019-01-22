<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\RedirectBundle\DependencyInjection\OroRedirectExtension;
use Oro\Bundle\RedirectBundle\Routing\MatchedUrlDecisionMaker;
use Oro\Bundle\RedirectBundle\Routing\NotInstalledMatchedUrlDecisionMaker;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroRedirectExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad()
    {
        $container = new ContainerBuilder();
        $container->setParameter('installed', true);

        $extension = new OroRedirectExtension();
        $extension->load([], $container);

        self::assertEquals(
            [
                [
                    'settings' => [
                        'resolved'                     => true,
                        'enable_direct_url'            => ['value' => true, 'scope' => 'app'],
                        'canonical_url_type'           => ['value' => 'system', 'scope' => 'app'],
                        'canonical_url_security_type'  => ['value' => 'secure', 'scope' => 'app'],
                        'redirect_generation_strategy' => ['value' => 'ask', 'scope' => 'app']
                    ]
                ]
            ],
            $container->getExtensionConfig('oro_redirect')
        );

        self::assertEquals(
            MatchedUrlDecisionMaker::class,
            $container->getDefinition('oro_redirect.routing.matched_url_decision_maker')->getClass()
        );
    }

    public function testLoadForNotInstalled()
    {
        $container = new ContainerBuilder();

        $extension = new OroRedirectExtension();
        $extension->load([], $container);

        self::assertEquals(
            NotInstalledMatchedUrlDecisionMaker::class,
            $container->getDefinition('oro_redirect.routing.matched_url_decision_maker')->getClass()
        );
    }
}
