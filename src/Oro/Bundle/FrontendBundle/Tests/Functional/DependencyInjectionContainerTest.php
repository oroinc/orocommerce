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

        $invalidParameters = [];
        foreach (array_keys($container->getParameterBag()->all()) as $name) {
            if (strpos($name, 'orob2b') === 0) {
                $invalidParameters[] = $name;
            }
        }
        $this->assertEmpty(
            $invalidParameters,
            "Invalid parameter names:\n" . implode("\n", $invalidParameters)
        );

        $invalidServices = [];
        foreach ($container->getServiceIds() as $name) {
            if (strpos($name, 'orob2b') === 0) {
                $invalidServices[] = $name;
            }
        }
        $this->assertEmpty(
            $invalidServices,
            "Invalid service names:\n" . implode("\n", $invalidParameters)
        );
    }
}
