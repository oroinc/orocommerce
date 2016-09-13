<?php

namespace Oro\Bundle\AccountBundle\Tests\Unit\Layout\DataProvider;

use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\AccountBundle\Entity\AccountUserRole;
use Oro\Bundle\AccountBundle\Form\Handler\AccountUserRoleUpdateFrontendHandler;
use Oro\Bundle\AccountBundle\Layout\DataProvider\FrontendAccountUserRoleFormProvider;

class FrontendAccountUserRoleFormProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var AccountUserRoleUpdateFrontendHandler|\PHPUnit_Framework_MockObject_MockObject */
    protected $handler;

    /** @var FrontendAccountUserRoleFormProvider */
    protected $provider;

    protected function setUp()
    {
        $this->handler = $this
            ->getMockBuilder('Oro\Bundle\AccountBundle\Form\Handler\AccountUserRoleUpdateFrontendHandler')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var FormFactory|\PHPUnit_Framework_MockObject_MockObject $formFactory */
        $formFactory = $this->getMockBuilder('Symfony\Component\Form\FormFactoryInterface')->getMock();
        $this->provider = new FrontendAccountUserRoleFormProvider($formFactory, $this->handler);
    }

    protected function tearDown()
    {
        unset($this->provider, $this->handler);
    }

    /**
     * @dataProvider getDataProvider
     *
     * @param AccountUserRole $role
     * @param string $route
     * @param array $routeParameters
     */
    public function testGetRoleForm(AccountUserRole $role, $route, array $routeParameters = [])
    {
        $form = $this->assertAccountUserRoleFormHandlerCalled($role);

        $actual = $this->provider->getRoleForm($role);

        $this->assertInstanceOf('Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor', $actual);
        $this->assertSame($form, $actual->getForm());

        $action = $actual->getAction();

        $this->assertEquals($route, $action->getRouteName());
        $this->assertEquals($routeParameters, $action->getRouteParameters());

        /** test local cache */
        $this->assertSame($actual, $this->provider->getRoleForm($role));
    }

    /**
     * @return array
     */
    public function getDataProvider()
    {
        return [
            [
                'role' => $this->getEntity('Oro\Bundle\AccountBundle\Entity\AccountUserRole'),
                'route' => 'oro_account_frontend_account_user_role_create'
            ],
            [
                'role' => $this->getEntity('Oro\Bundle\AccountBundle\Entity\AccountUserRole', ['id' => 42]),
                'route' => 'oro_account_frontend_account_user_role_update',
                'routeParameters' => ['id' => 42]
            ]
        ];
    }

    /**
     * @param AccountUserRole $role
     * @param string $method
     * @return \PHPUnit_Framework_MockObject_MockObject|FormInterface
     */
    protected function assertAccountUserRoleFormHandlerCalled(AccountUserRole $role, $method = 'TEST')
    {
        /** @var FormConfigInterface|\PHPUnit_Framework_MockObject_MockObject $config */
        $config = $this->getMock('Symfony\Component\Form\FormConfigInterface');
        $config->expects($this->any())
            ->method('getMethod')
            ->willReturn($method);

        /** @var FormView|\PHPUnit_Framework_MockObject_MockObject $config */
        $view = $this->getMock('Symfony\Component\Form\FormView');
        $view->vars = ['multipart' => null];

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->any())
            ->method('getConfig')
            ->willReturn($config);
        $form->expects($this->any())
            ->method('createView')
            ->willReturn($view);

        $this->handler->expects($this->once())
            ->method('createForm')
            ->with($role)
            ->willReturn($form);
        $this->handler->expects($this->once())
            ->method('process')
            ->with($role);

        return $form;
    }
}
