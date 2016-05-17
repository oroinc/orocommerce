<?php

namespace OroB2B\Bundle\FrontendBundle\Tests\Functional;

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

    public function testNotRecommendedClassParameters()
    {
        /** @var Container $container */
        $container = $this->getContainer();

        $notRecommendedClassParameters = [];
        foreach (array_keys($container->getParameterBag()->all()) as $name) {
            // orob2b*.class
            if (strpos($name, 'orob2b') === 0 && substr($name, -6) === '.class') {
                // not *.entity.* and not *.model.*
                if (strpos($name, '.entity.') === false && strpos($name, '.model.') === false) {
                    $notRecommendedClassParameters[] = $name;
                }
            }
        }

        $this->assertEmpty(
            $notRecommendedClassParameters,
            "Not recommended class parameters:\n" . implode("\n", $notRecommendedClassParameters)
        );

        $this->assertFalse(true, 'Test failed assertion');
    }
}
