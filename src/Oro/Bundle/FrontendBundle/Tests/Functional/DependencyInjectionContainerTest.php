<?php

namespace Oro\Bundle\FrontendBundle\Tests\Functional;

use Symfony\Component\DependencyInjection\Container;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class DependencyInjectionContainerTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();
    }

    public function testParameterAndServiceNames()
    {
        /** @var Container $container */
        $container = $this->getContainer();

        $invalidParameters = array_filter(
            array_keys($container->getParameterBag()->all()),
            function ($name) {
                return strpos($name, 'orob2b') === 0;
            }
        );
        $this->assertEmpty(
            $invalidParameters,
            "Invalid parameter names:\n" . implode("\n", $invalidParameters)
        );

        $invalidServices = array_filter(
            $container->getServiceIds(),
            function ($name) {
                return strpos($name, 'orob2b') === 0;
            }
        );
        $this->assertEmpty(
            $invalidServices,
            "Invalid service names:\n" . implode("\n", $invalidParameters)
        );
    }
}
