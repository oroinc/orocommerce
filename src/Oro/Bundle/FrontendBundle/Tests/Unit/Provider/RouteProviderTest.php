<?php

namespace Oro\Bundle\FrontendBundle\Tests\Unit\Provider;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

use Oro\Bundle\ActionBundle\Tests\Unit\Provider\RouteProviderTest as BaseRouteProviderTest;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\FrontendBundle\Provider\RouteProvider;
use Oro\Bundle\UserBundle\Entity\User;

class RouteProviderTest extends BaseRouteProviderTest
{
    /** @var RouteProvider */
    protected $provider;

    /** @var \PHPUnit_Framework_MockObject_MockObject|TokenStorageInterface */
    protected $tokenStorage;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);

        $this->provider = new RouteProvider(
            $this->provider,
            $this->tokenStorage,
            'oro_frontend_action_widget_form',
            'oro_frontend_action_widget_form_page',
            'oro_frontend_action_operation_execute',
            'oro_frontend_action_widget_buttons'
        );
    }

    /**
     * @param TokenInterface|null $token
     * @param array $expectedRoutes
     *
     * @dataProvider applicationRoutesProvider
     */
    public function testGetWidgetRoute(TokenInterface $token = null, array $expectedRoutes = [])
    {
        $this->tokenStorage->expects($this->any())
            ->method('getToken')
            ->willReturn($token);

        $this->assertEquals($expectedRoutes['widget'], $this->provider->getWidgetRoute());
    }

    /**
     * @param TokenInterface|null $token
     * @param array $expectedRoutes
     *
     * @dataProvider applicationRoutesProvider
     */
    public function testGetDialogRoute(TokenInterface $token = null, array $expectedRoutes = [])
    {
        $this->tokenStorage->expects($this->any())
            ->method('getToken')
            ->willReturn($token);

        $this->assertEquals($expectedRoutes['dialog'], $this->provider->getFormDialogRoute());
    }

    /**
     * @param TokenInterface|null $token
     * @param array $expectedRoutes
     *
     * @dataProvider applicationRoutesProvider
     */
    public function testGetPageRoute(TokenInterface $token = null, array $expectedRoutes = [])
    {
        $this->tokenStorage->expects($this->any())
            ->method('getToken')
            ->willReturn($token);

        $this->assertEquals($expectedRoutes['page'], $this->provider->getFormPageRoute());
    }

    /**
     * @param TokenInterface|null $token
     * @param array $expectedRoutes
     *
     * @dataProvider applicationRoutesProvider
     */
    public function testGetExecutionRoute(TokenInterface $token = null, array $expectedRoutes = [])
    {
        $this->tokenStorage->expects($this->any())
            ->method('getToken')
            ->willReturn($token);

        $this->assertEquals($expectedRoutes['execution'], $this->provider->getExecutionRoute());
    }

    /**
     * @return array
     */
    public function applicationRoutesProvider()
    {
        return [
            'backend user' => [
                'token' => $this->createToken(new User(), $this->any()),
                'routes' => [
                    'widget' => 'oro_action_widget_buttons',
                    'dialog' => 'oro_action_widget_form',
                    'page' => 'oro_action_widget_form_page',
                    'execution' => 'oro_action_operation_execute',
                ],
            ],
            'frontend user' => [
                'token' => $this->createToken(new CustomerUser(), $this->any()),
                'routes' => [
                    'widget' => 'oro_frontend_action_widget_buttons',
                    'dialog' => 'oro_frontend_action_widget_form',
                    'page' => 'oro_frontend_action_widget_form_page',
                    'execution' => 'oro_frontend_action_operation_execute',
                ],
            ],
            'not supported user' => [
                'token' => $this->createToken('anon.', $this->any()),
                'routes' => [
                    'widget' => 'oro_action_widget_buttons',
                    'dialog' => 'oro_action_widget_form',
                    'page' => 'oro_action_widget_form_page',
                    'execution' => 'oro_action_operation_execute',
                ],
            ],
            'empty token' => [
                'token' => null,
                'routes' => [
                    'widget' => 'oro_action_widget_buttons',
                    'dialog' => 'oro_action_widget_form',
                    'page' => 'oro_action_widget_form_page',
                    'execution' => 'oro_action_operation_execute',
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function getCurrentApplicationProvider()
    {
        return [
            'backend user' => [
                'token' => $this->createToken(new User(), $this->exactly(2)),
                'expectedResult' => 'default',
            ],
            'frontend user' => [
                'token' => $this->createToken(new CustomerUser()),
                'expectedResult' => 'commerce',
            ],
            'not supported user' => [
                'token' => $this->createToken('anon.', $this->exactly(2)),
                'expectedResult' => null,
            ],
            'empty token' => [
                'token' => null,
                'expectedResult' => null,
            ],
        ];
    }

    /**
     * @return array
     */
    public function isApplicationsValidDataProvider()
    {
        $user = new User();
        $customerUser = new CustomerUser();
        $otherUser = 'anon.';

        return [
            [
                'applications' => ['default', 'commerce'],
                'token' => $this->createToken($user, $this->exactly(2)),
                'expectedResult' => true
            ],
            [
                'applications' => ['default', 'commerce'],
                'token' => $this->createToken($customerUser),
                'expectedResult' => true
            ],
            [
                'applications' => ['default'],
                'token' => $this->createToken($user, $this->exactly(2)),
                'expectedResult' => true
            ],
            [
                'applications' => ['default'],
                'token' => $this->createToken($customerUser),
                'expectedResult' => false
            ],
            [
                'applications' => ['commerce'],
                'token' => $this->createToken($user, $this->exactly(2)),
                'expectedResult' => false
            ],
            [
                'applications' => ['commerce'],
                'token' => $this->createToken($customerUser),
                'expectedResult' => true
            ],
            [
                'applications' => ['default'],
                'token' => $this->createToken($otherUser, $this->exactly(2)),
                'expectedResult' => false
            ],
            [
                'applications' => ['commerce'],
                'token' => $this->createToken($otherUser, $this->exactly(2)),
                'expectedResult' => false
            ],
            [
                'applications' => ['default', 'commerce'],
                'token' => null,
                'expectedResult' => false
            ],
            [
                'applications' => [],
                'token' => null,
                'expectedResult' => true
            ],
        ];
    }

    /**
     * @param UserInterface|string $user
     * @param \PHPUnit_Framework_MockObject_Matcher_Invocation $expects
     * @return TokenInterface
     */
    protected function createToken($user, \PHPUnit_Framework_MockObject_Matcher_Invocation $expects = null)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|TokenInterface $token */
        $token = $this->createMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $token->expects($expects ?: $this->once())
            ->method('getUser')
            ->willReturn($user);

        return $token;
    }
}
