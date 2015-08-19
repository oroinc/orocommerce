<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Persistence\ObjectRepository;

use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Validator\Validation;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Form\Type\FrontendAccountUserRegistrationType;
use OroB2B\Bundle\AccountBundle\Entity\Account;

class FrontendAccountUserRegistrationTypeTest extends FormIntegrationTestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ConfigManager
     */
    protected $configManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|UserManager
     */
    protected $userManager;

    /**
     * @var FrontendAccountUserRegistrationType
     */
    protected $formType;

    /**
     * @var Account[]
     */
    protected static $accounts = [];

    protected function setUp()
    {
        parent::setUp();

        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->userManager = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\UserManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->formType = new FrontendAccountUserRegistrationType($this->configManager, $this->userManager);
        $this->formType->setDataClass('OroB2B\Bundle\AccountBundle\Entity\AccountUser');
    }

    protected function tearDown()
    {
        unset($this->configManager, $this->userManager, $this->formType);
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        return [
            new ValidatorExtension(Validation::createValidator())
        ];
    }

    /**
     * @dataProvider submitProvider
     *
     * @param AccountUser $defaultData
     * @param array $submittedData
     * @param AccountUser $expectedData
     * @param User $owner
     */
    public function testSubmit($defaultData, array $submittedData, $expectedData, User $owner)
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_b2b_account.default_account_owner')
            ->willReturn(42);

        $repository = $this->assertUserRepositoryCall();
        $repository->expects($this->once())
            ->method('find')
            ->with(42)
            ->willReturn($owner);

        $form = $this->factory->create($this->formType, $defaultData, []);

        $this->assertEquals($defaultData, $form->getData());
        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitProvider()
    {
        $entity = new AccountUser();
        $owner = new User();

        $expectedEntity = new AccountUser();
        $expectedEntity
            ->setFirstName('John')
            ->setLastName('Doe')
            ->setEmail('johndoe@example.com')
            ->setOwner($owner);

        $entity->setSalt($expectedEntity->getSalt());

        return [
            'new user' => [
                'defaultData' => $entity,
                'submittedData' => [
                    'firstName' => 'John',
                    'lastName' => 'Doe',
                    'email' => 'johndoe@example.com',
                    'plainPassword' => '123456'
                ],
                'expectedData' => $expectedEntity,
                'owner' => $owner
            ],
        ];
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ObjectRepository
     */
    protected function assertUserRepositoryCall()
    {
        $repository = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\Repository\UserRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->userManager->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);

        return $repository;
    }

    /**
     * Test getName
     */
    public function testGetName()
    {
        $this->assertEquals(FrontendAccountUserRegistrationType::NAME, $this->formType->getName());
    }

    /**
     * @param AccountUser $existingAccountUser
     * @param string $property
     * @param mixed $value
     */
    protected function setPropertyValue(AccountUser $existingAccountUser, $property, $value)
    {
        $class = new \ReflectionClass($existingAccountUser);
        $prop = $class->getProperty($property);
        $prop->setAccessible(true);
        $prop->setValue($existingAccountUser, $value);
    }
}
