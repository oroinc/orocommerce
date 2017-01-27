<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Layout\DataProvider;

use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserRole;
use Oro\Bundle\CustomerBundle\Form\Handler\CustomerUserRoleUpdateFrontendHandler;
use Oro\Bundle\CustomerBundle\Layout\DataProvider\FrontendCustomerUserRoleFormProvider;

class FrontendCustomerUserRoleFormProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var CustomerUserRoleUpdateFrontendHandler|\PHPUnit_Framework_MockObject_MockObject */
    protected $handler;

    /** @var FrontendCustomerUserRoleFormProvider */
    protected $provider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|UrlGeneratorInterface
     */
    protected $router;

    protected function setUp()
    {
        $this->handler = $this
            ->getMockBuilder('Oro\Bundle\CustomerBundle\Form\Handler\CustomerUserRoleUpdateFrontendHandler')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var FormFactory|\PHPUnit_Framework_MockObject_MockObject $formFactory */
        $formFactory = $this->getMockBuilder('Symfony\Component\Form\FormFactoryInterface')->getMock();
        $this->router = $this->createMock('Symfony\Component\Routing\Generator\UrlGeneratorInterface');

        $this->provider = new FrontendCustomerUserRoleFormProvider($formFactory, $this->handler, $this->router);
    }

    protected function tearDown()
    {
        unset($this->provider, $this->handler);
    }

    /**
     * @dataProvider getDataProvider
     *
     * @param CustomerUserRole $role
     * @param string $route
     * @param array $routeParameters
     */
    public function testGetRoleFormView(CustomerUserRole $role, $route, array $routeParameters = [])
    {
        $form = $this->assertCustomerUserRoleFormHandlerCalled($role);

        $this->router->expects($this->exactly(2))
            ->method('generate')
            ->with($route, $routeParameters);

        $actual = $this->provider->getRoleFormView($role);

        $this->assertInstanceOf(FormView::class, $actual);
        $this->assertSame($form->createView(), $actual);

        /** test local cache */
        $this->assertSame($actual, $this->provider->getRoleFormView($role));
    }

    /**
     * @dataProvider getDataProvider
     *
     * @param CustomerUserRole $role
     * @param string $route
     * @param array $routeParameters
     */
    public function testGetRoleForm(CustomerUserRole $role, $route, array $routeParameters = [])
    {
        $form = $this->assertCustomerUserRoleFormHandlerCalled($role);

        $this->router->expects($this->exactly(2))
            ->method('generate')
            ->with($route, $routeParameters);

        $actual = $this->provider->getRoleForm($role);

        $this->assertInstanceOf(FormInterface::class, $actual);
        $this->assertSame($form, $actual);

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
                'role' => $this->getEntity('Oro\Bundle\CustomerBundle\Entity\CustomerUserRole'),
                'route' => 'oro_customer_frontend_customer_user_role_create'
            ],
            [
                'role' => $this->getEntity('Oro\Bundle\CustomerBundle\Entity\CustomerUserRole', ['id' => 42]),
                'route' => 'oro_customer_frontend_customer_user_role_update',
                'routeParameters' => ['id' => 42]
            ]
        ];
    }

    /**
     * @param CustomerUserRole $role
     * @param string $method
     * @return \PHPUnit_Framework_MockObject_MockObject|FormInterface
     */
    protected function assertCustomerUserRoleFormHandlerCalled(CustomerUserRole $role, $method = 'TEST')
    {
        /** @var FormConfigInterface|\PHPUnit_Framework_MockObject_MockObject $config */
        $config = $this->createMock('Symfony\Component\Form\FormConfigInterface');
        $config->expects($this->any())
            ->method('getMethod')
            ->willReturn($method);

        /** @var FormView|\PHPUnit_Framework_MockObject_MockObject $config */
        $view = $this->createMock('Symfony\Component\Form\FormView');
        $view->vars = ['multipart' => null];

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->createMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->any())
            ->method('getConfig')
            ->willReturn($config);
        $form->expects($this->any())
            ->method('createView')
            ->willReturn($view);

        $this->handler->expects($this->any())
            ->method('createForm')
            ->with($role)
            ->willReturn($form);
        $this->handler->expects($this->any())
            ->method('process')
            ->with($role);

        return $form;
    }
}
