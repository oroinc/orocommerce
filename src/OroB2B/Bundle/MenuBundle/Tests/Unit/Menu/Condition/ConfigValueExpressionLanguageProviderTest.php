<?php

namespace OroB2B\Bundle\MenuBundle\Tests\Unit\Menu\Condition;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;

use OroB2B\Bundle\MenuBundle\Menu\Condition\ConfigValueExpressionLanguageProvider;

class ConfigValueExpressionLanguageProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ConfigValueExpressionLanguageProvider
     */
    protected $provider;

    /**
     * @var ContainerInterface
     */
    protected $container;

    public function setUp()
    {
        $this->container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $this->provider = new ConfigValueExpressionLanguageProvider($this->container);
    }

    public function testGetFunctions()
    {
        $functions = $this->provider->getFunctions();
        $this->assertCount(1, $functions);
        /** @var ExpressionFunction $function */
        $function = array_shift($functions);

        $paramName = 'param.name';

        $this->assertInstanceOf('Symfony\Component\ExpressionLanguage\ExpressionFunction', $function);
        $this->assertEquals(
            sprintf('config_value(%s)', $paramName),
            call_user_func($function->getCompiler(), $paramName)
        );
        $this->assertNull(call_user_func($function->getEvaluator(), [], $paramName));

        $configValue = 'config_value';
        $this->container->expects($this->once())
            ->method('getParameter')
            ->with($paramName)
            ->willReturn($configValue);
        $this->assertEquals($configValue, call_user_func($function->getEvaluator(), [], $paramName));
    }
}
