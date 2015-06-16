<?php

namespace OroB2B\Bundle\CustomerBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Validator\Validation;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;
use OroB2B\Bundle\CustomerBundle\Form\Type\FrontendAccountUserRegistrationType;
use OroB2B\Bundle\CustomerBundle\Entity\Customer;

class FrontendAccountUserRegistrationTypeTest extends FormIntegrationTestCase
{
    /**
     * @var FrontendAccountUserRegistrationType
     */
    protected $formType;

    /**
     * @var Customer[]
     */
    protected static $customers = [];

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->formType = new FrontendAccountUserRegistrationType();
        $this->formType->setDataClass('OroB2B\Bundle\CustomerBundle\Entity\AccountUser');
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

        $expectedEntity = new AccountUser();
        $expectedEntity->setFirstName('John');
        $expectedEntity->setLastName('Doe');
        $expectedEntity->setEmail('johndoe@example.com');

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
                'expectedData' => $expectedEntity
            ],
        ];
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
