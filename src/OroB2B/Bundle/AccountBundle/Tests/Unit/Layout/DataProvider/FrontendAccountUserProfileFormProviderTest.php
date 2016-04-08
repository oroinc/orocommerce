<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Layout\DataProvider;

use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

use Oro\Component\Layout\ContextDataCollection;
use Oro\Component\Layout\ContextInterface;
use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Layout\DataProvider\FrontendAccountUserProfileFormProvider;

class FrontendAccountUserProfileFormProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var FormFactory|\PHPUnit_Framework_MockObject_MockObject */
    protected $formFactory;

    /** @var FrontendAccountUserProfileFormProvider */
    protected $provider;

    protected function setUp()
    {
        $this->formFactory = $this
            ->getMockBuilder('Symfony\Component\Form\FormFactory')
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->provider = new FrontendAccountUserProfileFormProvider($this->formFactory);
    }

    protected function tearDown()
    {
        unset($this->provider, $this->handler);
    }

    /**
     * @dataProvider getDataProvider
     *
     * @param AccountUser $accountUser
     * @param string $route
     * @param array $routeParameters
     */
    public function testGetData(AccountUser $accountUser, $route, array $routeParameters = [])
    {
        $form = $this->assertAccountUserProfileFormHandlerCalled();
        $context = $this->getLayoutContext($accountUser);
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
                'accountUser' => $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountUser', ['id' => 42]),
                'route' => 'orob2b_account_frontend_account_user_profile_update',
                'routeParameters' => ['id' => 42]
            ]
        ];
    }

    public function testGetForm()
    {
        /** @var AccountUser $accountUser */
        $accountUser = $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountUser', ['id' => 42]);

        $form = $this->assertAccountUserProfileFormHandlerCalled();

        $this->assertSame($form, $this->provider->getForm($accountUser));

        /** test local cache */
        $this->assertSame($form, $this->provider->getForm($accountUser));
    }

    /**
     * @param AccountUser $accountUser
     * @return ContextInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getLayoutContext(AccountUser $accountUser)
    {
        /** @var ContextDataCollection|\PHPUnit_Framework_MockObject_MockObject $data */
        $data = $this->getMockBuilder('Oro\Component\Layout\ContextDataCollection')
            ->disableOriginalConstructor()
            ->getMock();
        $data->expects($this->exactly(2))
            ->method('get')
            ->with('entity')
            ->willReturn($accountUser);

        /** @var ContextInterface|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->getMock('Oro\Component\Layout\ContextInterface');
        $context->expects($this->exactly(2))
            ->method('data')
            ->willReturn($data);

        return $context;
    }

    /**
     * @param string $method
     * @return \PHPUnit_Framework_MockObject_MockObject|FormInterface
     */
    protected function assertAccountUserProfileFormHandlerCalled($method = 'TEST')
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

        $this->formFactory->expects($this->once())
            ->method('create')
            ->willReturn($form);

        return $form;
    }
}
