<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Entity;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserManager;

class CustomerUserManagerTest extends \PHPUnit_Framework_TestCase
{
    const USER_CLASS = 'Oro\Bundle\CustomerBundle\Entity\CustomerUser';

    /**
     * @var CustomerUserManager
     */
    protected $userManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $om;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $ef;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $emailProcessor;

    protected function setUp()
    {
        $this->ef = $this->createMock('Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface');
        $this->om = $this->createMock('Doctrine\Common\Persistence\ObjectManager');
        $this->registry = $this->createMock('Doctrine\Common\Persistence\ManagerRegistry');

        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->emailProcessor = $this->getMockBuilder('Oro\Bundle\CustomerBundle\Mailer\Processor')
            ->disableOriginalConstructor()
            ->getMock();

        $container = $this->createMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $container->expects($this->any())
            ->method('get')
            ->will(
                $this->returnValueMap(
                    [
                        [
                            'oro_config.manager',
                            ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
                            $this->configManager
                        ],
                        [
                            'oro_customer.mailer.processor',
                            ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
                            $this->emailProcessor
                        ]
                    ]
                )
            );

        $this->userManager = new CustomerUserManager(static::USER_CLASS, $this->registry, $this->ef);
        $this->userManager->setContainer($container);
    }

    public function testConfirmRegistration()
    {
        $password = 'test';

        $user = new CustomerUser();
        $user->setConfirmed(false);
        $user->setPlainPassword($password);

        $this->emailProcessor->expects($this->once())
            ->method('sendWelcomeNotification')
            ->with($user, false);

        $this->userManager->confirmRegistration($user);

        $this->assertTrue($user->isConfirmed());
    }

    /**
     * @dataProvider welcomeEmailDataProvider
     *
     * @param bool $sendPassword
     */
    public function testSendWelcomeEmail($sendPassword)
    {
        $password = 'test';

        $user = new CustomerUser();
        $user->setPlainPassword($password);

        $this->emailProcessor->expects($this->once())
            ->method('sendWelcomeNotification')
            ->with($user, $sendPassword ? $password : null);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_customer.send_password_in_welcome_email')
            ->willReturn($sendPassword);

        $this->userManager->sendWelcomeEmail($user);
    }

    /**
     * @return array
     */
    public function welcomeEmailDataProvider()
    {
        return [
            ['sendPassword' => true],
            ['sendPassword' => false]
        ];
    }

    public function testGeneratePassword()
    {
        $password = $this->userManager->generatePassword(10);
        $this->assertNotEmpty($password);
        $this->assertRegExp('/\w+/', $password);
        $this->assertLessThanOrEqual(10, strlen($password));
    }

    public function testRegisterConfirmationRequired()
    {
        $password = 'test1Q';

        $user = new CustomerUser();
        $user->setEnabled(false);
        $user->setPlainPassword($password);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_customer.confirmation_required')
            ->will($this->returnValue(true));

        $this->emailProcessor->expects($this->once())
            ->method('sendConfirmationEmail')
            ->with($user);

        $this->userManager->register($user);

        $this->assertFalse($user->isEnabled());
        $this->assertNotEmpty($user->getConfirmationToken());
    }

    public function testRegisterConfirmationNotRequired()
    {
        $password = 'test1Q';

        $user = new CustomerUser();
        $user->setConfirmed(false);
        $user->setPlainPassword($password);

        $this->configManager->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap(
                [
                    ['oro_customer.confirmation_required', false, false, null, false],
                    ['oro_customer.send_password_in_welcome_email', false, false, null, true]
                ]
            );

        $this->emailProcessor->expects($this->once())
            ->method('sendWelcomeNotification')
            ->with($user, $password);

        $this->userManager->register($user);

        $this->assertTrue($user->isConfirmed());
    }

    public function testSendResetPasswordEmail()
    {
        $user = new CustomerUser();
        $this->emailProcessor->expects($this->once())
            ->method('sendResetPasswordEmail')
            ->with($user);
        $this->userManager->sendResetPasswordEmail($user);
    }

    /**
     * @dataProvider requiredDataProvider
     * @param bool $required
     */
    public function testIsConfirmationRequired($required)
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_customer.confirmation_required')
            ->will($this->returnValue($required));

        $this->assertEquals($required, $this->userManager->isConfirmationRequired());
    }

    /**
     * @return array
     */
    public function requiredDataProvider()
    {
        return [
            [true],
            [false]
        ];
    }
}
