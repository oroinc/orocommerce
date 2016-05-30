<?php

namespace OroB2B\Bundle\FrontendBundle\Tests\Unit\Helper;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Oro\Bundle\ActionBundle\Tests\Unit\Helper\ApplicationsHelperTest;
use Oro\Bundle\UserBundle\Entity\User;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\FrontendBundle\Helper\ActionApplicationsHelper;

class ActionApplicationsHelperTest extends ApplicationsHelperTest
{
    /** @var ActionApplicationsHelper */
    protected $helper;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->helper = new ActionApplicationsHelper($this->tokenStorage);
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

        $this->assertEquals($expectedRoutes['widget'], $this->helper->getWidgetRoute());
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

        $this->assertEquals($expectedRoutes['dialog'], $this->helper->getDialogRoute());
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

        $this->assertEquals($expectedRoutes['execution'], $this->helper->getExecutionRoute());
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
                    'execution' => 'oro_action_operation_execute',
                ],
            ],
            'frontend user' => [
                'token' => $this->createToken(new AccountUser(), $this->any()),
                'routes' => [
                    'widget' => 'orob2b_frontend_action_widget_buttons',
                    'dialog' => 'orob2b_frontend_action_widget_form',
                    'execution' => 'orob2b_frontend_action_operation_execute',
                ],
            ],
            'not supported user' => [
                'token' => $this->createToken('anon.', $this->any()),
                'routes' => [
                    'widget' => 'oro_action_widget_buttons',
                    'dialog' => 'oro_action_widget_form',
                    'execution' => 'oro_action_operation_execute',
                ],
            ],
            'empty token' => [
                'token' => null,
                'routes' => [
                    'widget' => 'oro_action_widget_buttons',
                    'dialog' => 'oro_action_widget_form',
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
                'expectedResult' => 'backend',
            ],
            'frontend user' => [
                'token' => $this->createToken(new AccountUser()),
                'expectedResult' => 'frontend',
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
        $accountUser = new AccountUser();
        $otherUser = 'anon.';

        return [
            [
                'applications' => ['backend', 'frontend'],
                'token' => $this->createToken($user, $this->exactly(2)),
                'expectedResult' => true
            ],
            [
                'applications' => ['backend', 'frontend'],
                'token' => $this->createToken($accountUser),
                'expectedResult' => true
            ],
            [
                'applications' => ['backend'],
                'token' => $this->createToken($user, $this->exactly(2)),
                'expectedResult' => true
            ],
            [
                'applications' => ['backend'],
                'token' => $this->createToken($accountUser),
                'expectedResult' => false
            ],
            [
                'applications' => ['frontend'],
                'token' => $this->createToken($user, $this->exactly(2)),
                'expectedResult' => false
            ],
            [
                'applications' => ['frontend'],
                'token' => $this->createToken($accountUser),
                'expectedResult' => true
            ],
            [
                'applications' => ['backend'],
                'token' => $this->createToken($otherUser, $this->exactly(2)),
                'expectedResult' => false
            ],
            [
                'applications' => ['frontend'],
                'token' => $this->createToken($otherUser, $this->exactly(2)),
                'expectedResult' => false
            ],
            [
                'applications' => ['backend', 'frontend'],
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
}
