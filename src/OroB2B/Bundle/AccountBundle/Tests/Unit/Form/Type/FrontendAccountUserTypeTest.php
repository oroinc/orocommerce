<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Validator\Validation;

use Oro\Bundle\FormBundle\Form\Type\OroDateType;

use Oro\Bundle\UserBundle\Tests\Unit\Stub\ChangePasswordTypeStub;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Form\Type\FrontendAccountUserType;
use OroB2B\Bundle\AccountBundle\Entity\Account;

class FrontendAccountUserTypeTest extends FormIntegrationTestCase
{
    /**
     * @var FrontendAccountUserType
     */
    protected $formType;

    /**
     * @var Account[]
     */
    protected static $accounts = [];

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->formType = new FrontendAccountUserType();
        $this->formType->setDataClass('OroB2B\Bundle\AccountBundle\Entity\AccountUser');
    }

    protected function tearDown()
    {
        parent::tearDown();

        unset($this->formType);
        self::$accounts = [];
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    OroDateType::NAME => new OroDateType(),
                    ChangePasswordTypeStub::NAME => new ChangePasswordTypeStub()
                ],
                []
            ),
            new ValidatorExtension(Validation::createValidator())
        ];
    }

    /**
     * @param AccountUser $defaultData
     * @param array $submittedData
     * @param AccountUser $expectedData
     * @dataProvider submitProvider
     */
    public function testSubmit($defaultData, array $submittedData, $expectedData)
    {
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

        $existingEntity = new AccountUser();
        $this->setPropertyValue($existingEntity, 'id', 42);

        $existingEntity->setFirstName('John');
        $existingEntity->setLastName('Doe');
        $existingEntity->setEmail('johndoe@example.com');
        $existingEntity->setPassword('123456');

        $updatedEntity = clone $existingEntity;
        $updatedEntity->setFirstName('John UP');
        $updatedEntity->setLastName('Doe UP');
        $updatedEntity->setEmail('johndoe_up@example.com');

        return [
            'new user' => [
                'defaultData' => $entity,
                'submittedData' => [],
                'expectedData' => $entity
            ],
            'updated user' => [
                'defaultData' => $existingEntity,
                'submittedData' => [
                    'firstName' => $updatedEntity->getFirstName(),
                    'lastName' => $updatedEntity->getLastName(),
                    'email' => $updatedEntity->getEmail()
                ],
                'expectedData' => $updatedEntity
            ]
        ];
    }

    /**
     * Test getName
     */
    public function testGetName()
    {
        $this->assertEquals(FrontendAccountUserType::NAME, $this->formType->getName());
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
