<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Menu\Condition;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;

use OroB2B\Bundle\AccountBundle\Security\AccountUserProvider;
use OroB2B\Bundle\AccountBundle\Menu\Condition\LoggedInExpressionLanguageProvider;

class LoggedInExpressionLanguageProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LoggedInExpressionLanguageProvider
     */
    protected $provider;

    /**
     * @var AccountUserProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $accountUserProvider;

    public function setUp()
    {
        $this->accountUserProvider = $this->getMockBuilder('OroB2B\Bundle\AccountBundle\Security\AccountUserProvider')
            ->disableOriginalConstructor()->getMock();
        $this->provider = new LoggedInExpressionLanguageProvider($this->accountUserProvider);
    }

    public function testGetFunctions()
    {
        $functions = $this->provider->getFunctions();
        $this->assertCount(1, $functions);
        /** @var ExpressionFunction $function */
        $function = array_shift($functions);

        $this->assertInstanceOf('Symfony\Component\ExpressionLanguage\ExpressionFunction', $function);
        $this->assertEquals('is_logged_in()', call_user_func($function->getCompiler()));

        $this->accountUserProvider->expects($this->at(0))
            ->method('getLoggedUser')
            ->willReturn(null);

        $this->accountUserProvider->expects($this->at(1))
            ->method('getLoggedUser')
            ->willReturn($this->getMock('OroB2B\Bundle\AccountBundle\Entity\AccountUser'));

        $this->assertFalse(call_user_func($function->getEvaluator()));
        $this->assertTrue(call_user_func($function->getEvaluator()));
    }
}
