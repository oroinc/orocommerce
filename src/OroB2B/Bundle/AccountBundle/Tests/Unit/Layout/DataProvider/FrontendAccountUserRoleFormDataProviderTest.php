<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Layout\DataProvider;

use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

use Oro\Component\Layout\ContextDataCollection;
use Oro\Component\Layout\ContextInterface;
use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\AccountBundle\Entity\AccountUserRole;
use OroB2B\Bundle\AccountBundle\Form\Handler\AccountUserRoleUpdateFrontendHandler;
use OroB2B\Bundle\AccountBundle\Layout\DataProvider\FrontendAccountUserRoleFormDataProvider;

class FrontendAccountUserRoleFormDataProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var AccountUserRoleUpdateFrontendHandler|\PHPUnit_Framework_MockObject_MockObject */
    protected $handler;

    /** @var FrontendAccountUserRoleFormDataProvider */
    protected $provider;

    protected function setUp()
    {
        $this->handler = $this
            ->getMockBuilder('OroB2B\Bundle\AccountBundle\Form\Handler\AccountUserRoleUpdateFrontendHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new FrontendAccountUserRoleFormDataProvider($this->handler);
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
    public function testGetData(AccountUserRole $role, $route, array $routeParameters = [])
    {
        $form = $this->assertAccountUserRoleFormHandlerCalled($role);
        $context = $this->getLayoutContext($role);

        $actual = $this->provider->getData($context);

        $this->assertInstanceOf('Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor', $actual);
        $this->assertSame($form, $actual->getForm());

        $action = $actual->getAction();

        $this->assertEquals($route, $action->getRouteName());
        $this->assertEquals($routeParameters, $action->getRouteParameters());

        /** test local cache */
        $this->assertSame($actual, $this->provider->getData($context));
    }

    /**
     * @return array
     */
    public function getDataProvider()
    {
        return [
            [
                'role' => $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountUserRole'),
                'route' => 'orob2b_account_frontend_account_user_role_create'
            ],
            [
                'role' => $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountUserRole', ['id' => 42]),
                'route' => 'orob2b_account_frontend_account_user_role_update',
                'routeParameters' => ['id' => 42]
            ]
        ];
    }

    public function testGetForm()
    {
        /** @var AccountUserRole $role */
        $role = $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountUserRole', ['id' => 42]);

        $form = $this->assertAccountUserRoleFormHandlerCalled($role);

        $this->assertSame($form, $this->provider->getForm($role));

        /** test local cache */
        $this->assertSame($form, $this->provider->getForm($role));
    }

    /**
     * @param AccountUserRole $role
     * @return ContextInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getLayoutContext(AccountUserRole $role)
    {
        /** @var ContextDataCollection|\PHPUnit_Framework_MockObject_MockObject $data */
        $data = $this->getMockBuilder('Oro\Component\Layout\ContextDataCollection')
            ->disableOriginalConstructor()
            ->getMock();
        $data->expects($this->exactly(2))
            ->method('get')
            ->with('entity')
            ->willReturn($role);

        /** @var ContextInterface|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->getMock('Oro\Component\Layout\ContextInterface');
        $context->expects($this->exactly(2))
            ->method('data')
            ->willReturn($data);

        return $context;
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
