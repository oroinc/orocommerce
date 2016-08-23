<?php

namespace Oro\Bundle\MenuBundle\Tests\Unit\Menu\Condition;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\MenuBundle\Menu\Condition\ConfigValueExpressionLanguageProvider;

class ConfigValueExpressionLanguageProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ConfigValueExpressionLanguageProvider
     */
    protected $provider;

    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

    public function setUp()
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()->getMock();
        $this->provider = new ConfigValueExpressionLanguageProvider($this->configManager);
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
        $this->configManager->expects($this->once())
            ->method('get')
            ->with($paramName)
            ->willReturn($configValue);
        $this->assertEquals($configValue, call_user_func($function->getEvaluator(), [], $paramName));
    }
}
