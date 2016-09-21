<?php

namespace Oro\Bundle\MenuBundle\Tests\Unit\Menu\Condition;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\MenuBundle\Menu\Condition\LoggedInExpressionLanguageProvider;

class LoggedInExpressionLanguageProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LoggedInExpressionLanguageProvider
     */
    protected $provider;

    /**
     * @var SecurityFacade|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityFacade;

    public function setUp()
    {
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()->getMock();
        $this->provider = new LoggedInExpressionLanguageProvider($this->securityFacade);
    }

    /**
     * @dataProvider getFunctionsDataProvider
     * @param bool $isLoggedUser
     * @param bool $expectedData
     */
    public function testGetFunctions($isLoggedUser, $expectedData)
    {
        $functions = $this->provider->getFunctions();
        $this->assertCount(1, $functions);
        /** @var ExpressionFunction $function */
        $function = array_shift($functions);

        $this->assertInstanceOf('Symfony\Component\ExpressionLanguage\ExpressionFunction', $function);
        $this->assertEquals('is_logged_in()', call_user_func($function->getCompiler()));

        $loggedUser = null;
        if ($isLoggedUser) {
            $loggedUser = $this->getMock('Oro\Bundle\CustomerBundle\Entity\AccountUser');
        }

        $this->securityFacade->expects($this->once())
            ->method('getLoggedUser')
            ->willReturn($loggedUser);

        $this->assertEquals($expectedData, call_user_func($function->getEvaluator()));
    }

    /**
     * @return array
     */
    public function getFunctionsDataProvider()
    {
        return [
            [
                'isLoggedUser' => false,
                'expectedData' => false,
            ],
            [
                'isLoggedUser' => true,
                'expectedData' => true,
            ]
        ];
    }
}
