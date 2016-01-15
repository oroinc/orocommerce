<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Menu\Condition;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use OroB2B\Bundle\AccountBundle\Menu\Condition\LoggedInExpressionLanguageProvider;

class LoggedInExpressionLanguageProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LoggedInExpressionLanguageProvider
     */
    protected $provider;

    /**
     * @var TokenStorageInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $tokenStorage;

    public function setUp()
    {
        $this->tokenStorage =
            $this->getMock('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface');
        $this->provider = new LoggedInExpressionLanguageProvider($this->tokenStorage);
    }

    public function testGetFunctions()
    {
        $functions = $this->provider->getFunctions();
        $this->assertCount(1, $functions);
        /** @var ExpressionFunction $function */
        $function = array_shift($functions);

        $this->assertInstanceOf('Symfony\Component\ExpressionLanguage\ExpressionFunction', $function);
        $this->assertEquals('is_logged_in()', call_user_func($function->getCompiler()));

        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $user = $this->getMock('OroB2B\Bundle\AccountBundle\Entity\AccountUser');

        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $this->assertTrue(call_user_func($function->getEvaluator()));
    }
}
