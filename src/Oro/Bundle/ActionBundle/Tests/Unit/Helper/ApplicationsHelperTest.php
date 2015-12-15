<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Helper;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

use Oro\Bundle\ActionBundle\Helper\ApplicationsHelper;
use Oro\Bundle\ActionBundle\Model\Action;
use Oro\Bundle\ActionBundle\Model\ActionDefinition;
use Oro\Bundle\UserBundle\Entity\User;

class ApplicationsHelperTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|TokenStorageInterface */
    protected $tokenStorage;

    /** @var ApplicationsHelper */
    protected $helper;

    protected function setUp()
    {
        $this->tokenStorage = $this
            ->getMock('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface');

        $this->helper = new ApplicationsHelper($this->tokenStorage);
    }

    protected function tearDown()
    {
        unset($this->helper, $this->tokenStorage);
    }

    /**
     * @dataProvider isApplicationsValidDataProvider
     *
     * @param array $applications
     * @param TokenInterface|null $token
     * @param bool $expectedResult
     */
    public function testIsApplicationsValid(array $applications, $token, $expectedResult)
    {
        $this->tokenStorage->expects($this->atLeastOnce())
            ->method('getToken')
            ->willReturn($token);

        $this->assertEquals($expectedResult, $this->helper->isApplicationsValid($this->createAction($applications)));
    }

    /**
     * @return array
     */
    public function isApplicationsValidDataProvider()
    {
        $user = new User();
        $otherUser = 'anon.';

        return [
            [
                'applications' => ['backend', 'frontend'],
                'token' => $this->createToken($user),
                'expectedResult' => true
            ],
            [
                'applications' => ['backend'],
                'token' => $this->createToken($user),
                'expectedResult' => true
            ],
            [
                'applications' => ['frontend'],
                'token' => $this->createToken($user),
                'expectedResult' => false
            ],
            [
                'applications' => [],
                'token' => $this->createToken($user),
                'expectedResult' => true
            ],
            [
                'applications' => ['backend', 'frontend'],
                'token' => $this->createToken($otherUser),
                'expectedResult' => true
            ],
            [
                'applications' => ['backend'],
                'token' => $this->createToken($otherUser),
                'expectedResult' => false
            ],
            [
                'applications' => ['frontend'],
                'token' => $this->createToken($otherUser),
                'expectedResult' => true
            ],
            [
                'applications' => [],
                'token' => $this->createToken($otherUser),
                'expectedResult' => true
            ],
            [
                'applications' => ['backend', 'frontend'],
                'token' => null,
                'expectedResult' => false
            ],
            [
                'applications' => ['backend'],
                'token' => null,
                'expectedResult' => false
            ],
            [
                'applications' => ['frontend'],
                'token' => null,
                'expectedResult' => false
            ],
            [
                'applications' => [],
                'token' => null,
                'expectedResult' => false
            ],
        ];
    }

    /**
     * @param array $applications
     * @return Action
     */
    protected function createAction(array $applications)
    {
        $definition = new ActionDefinition();
        $definition->setApplications($applications);

        /** @var \PHPUnit_Framework_MockObject_MockObject|Action $action */
        $action = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\Action')
            ->disableOriginalConstructor()
            ->getMock();
        $action->expects($this->any())
            ->method('getDefinition')
            ->willReturn($definition);

        return $action;
    }

    /**
     * @param UserInterface|string $user
     * @return TokenInterface
     */
    protected function createToken($user)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|TokenInterface $token */
        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        return $token;
    }
}
