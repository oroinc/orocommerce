<?php

namespace Oro\Bundle\CommerceMenuBundle\Tests\Unit\Menu\Condition;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;

use Oro\Bundle\CommerceMenuBundle\Menu\Condition\LoggedInExpressionLanguageProvider;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\SecurityBundle\SecurityFacade;

use Oro\Component\DependencyInjection\ServiceLink;

class LoggedInExpressionLanguageProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var LoggedInExpressionLanguageProvider */
    private $provider;

    /** @var SecurityFacade|\PHPUnit_Framework_MockObject_MockObject */
    private $securityFacade;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->securityFacade = $this->getMockBuilder(SecurityFacade::class)->disableOriginalConstructor()->getMock();

        $securityFacadeLink = $this->getSecurityFacadeLink($this->securityFacade);
        $this->provider = new LoggedInExpressionLanguageProvider($securityFacadeLink);
    }

    /**
     * @param \PHPUnit_Framework_MockObject_MockObject $securityFacade
     *
     * @return ServiceLink|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getSecurityFacadeLink(\PHPUnit_Framework_MockObject_MockObject $securityFacade)
    {
        /** @var ServiceLink|\PHPUnit_Framework_MockObject_MockObject $securityFacadeLink */
        $securityFacadeLink = $this->getMockBuilder(ServiceLink::class)->disableOriginalConstructor()->getMock();
        $securityFacadeLink
            ->expects($this->any())
            ->method('getService')
            ->willReturn($securityFacade);

        return $securityFacadeLink;
    }

    /**
     * @dataProvider getFunctionsDataProvider
     *
     * @param bool $isLoggedUser
     * @param bool $expectedData
     */
    public function testGetFunctions($isLoggedUser, $expectedData)
    {
        $functions = $this->provider->getFunctions();
        $this->assertCount(1, $functions);

        /** @var ExpressionFunction $function */
        $function = array_shift($functions);

        $this->assertInstanceOf(ExpressionFunction::class, $function);
        $this->assertEquals('is_logged_in()', call_user_func($function->getCompiler()));

        $loggedUser = null;
        if ($isLoggedUser) {
            $loggedUser = $this->createMock(CustomerUser::class);
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
