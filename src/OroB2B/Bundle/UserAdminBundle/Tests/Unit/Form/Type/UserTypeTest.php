<?php

namespace OroB2B\Bundle\UserAdminBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Extension\Core\CoreExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormFactoryBuilder;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Validator\Validation;

use Doctrine\Common\Persistence\ManagerRegistry;

use OroB2B\Bundle\UserAdminBundle\Entity\User;
use OroB2B\Bundle\UserAdminBundle\Entity\Group;
use OroB2B\Bundle\UserAdminBundle\Form\Type\UserType;
use OroB2B\Bundle\UserAdminBundle\Tests\Unit\Form\Type\Stub\EntityType;

class UserTypeTest extends FormIntegrationTestCase
{
    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var UserType
     */
    protected $formType;

    /**
     * @var Group
     */
    protected $group;

    /**
     * @var \Symfony\Component\Translation\TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $translator;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->group = new Group('test');

        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');

        $builder = new FormFactoryBuilder();
        $builder->addExtensions($this->getExtensions())
            ->addExtension(new CoreExtension());

        $this->factory = $builder->getFormFactory();
        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

        $this->formType = new UserType($this->translator);
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $entityType = new EntityType($this->registry);
        $entityType->setChoices([new Group('TestGroup01'), new Group('TestGroup02')]);

        return [
            new PreloadedExtension(['entity' => $entityType], []),
            new ValidatorExtension(Validation::createValidator())
        ];
    }

    /**
     * @param $defaultData
     * @param $submittedData
     * @param $expectedData
     * @dataProvider submitProvider
     */
    public function testSubmit($defaultData, $submittedData, $expectedData)
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
        $newUser = new User();

        $existingUser = new User();

        $class = new \ReflectionClass($existingUser);
        $prop  = $class->getProperty('id');
        $prop->setAccessible(true);

        $prop->setValue($existingUser, 42);

        $existingUser->setFirstName('John');
        $existingUser->setLastName('Doe');
        $existingUser->setEmail('johndoe@example.com');
        $existingUser->setPassword('123456');

        return [
            'new user' => [
                'defaultData' => $existingUser,
                'submittedData' => [],
                'expectedData' => $existingUser
            ],
            'existing user' => [
                'defaultData' => $newUser,
                'submittedData' => [],
                'expectedData' => $newUser
            ]
        ];
    }

    /**
     * Test getName
     */
    public function testGetName()
    {
        $this->assertEquals(UserType::NAME, $this->formType->getName());
    }
}
