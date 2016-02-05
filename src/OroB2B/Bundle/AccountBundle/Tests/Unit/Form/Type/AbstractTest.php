<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Type;

use Symfony\Component\Validator\ConstraintValidatorInterface;

use Oro\Component\Testing\Unit\FormIntegrationTestCase;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;

abstract class AbstractTest extends FormIntegrationTestCase
{
    /**
     * @var ConstraintValidatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $uniqueEntityValidator;

    /**
     * @param bool $isValid
     * @param array $submittedData
     * @param mixed $expectedData
     * @param mixed $defaultData
     *
     * @dataProvider submitProvider
     */
    public function testSubmit($isValid, $submittedData, $expectedData, $defaultData = null)
    {
        $form = $this->factory->create($this->formType, $defaultData, []);

        $this->assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);

        $this->assertEquals($isValid, $form->isValid());

        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    abstract public function submitProvider();

    /**
     * {@inheritdoc}
     */
    protected function getValidators()
    {
        $this->uniqueEntityValidator = $this->getMock('Symfony\Component\Validator\ConstraintValidatorInterface');

        return [
            'doctrine.orm.validator.unique' => $this->uniqueEntityValidator,
        ];
    }

    /**
     * @param int $id
     * @return AccountUser
     */
    protected function getAccountUser($id)
    {
        /* @var $accountUser AccountUser */
        $accountUser = $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountUser', $id);

        $accountUser
            ->setFirstName('FirstName')
            ->setLastName('LastName')
            ->setEmail('test@example.com')
            ->setAccount($this->getEntity('OroB2B\Bundle\AccountBundle\Entity\Account', $id))
            ->setOrganization($this->getEntity('Oro\Bundle\OrganizationBundle\Entity\Organization', $id))
            ->addRole($this->getMock('Symfony\Component\Security\Core\Role\Role', null, ['ROLE1']))
        ;

        return $accountUser;
    }

    /**
     * @param string $className
     * @param int $id
     * @param string $primaryKey
     * @return object
     */
    protected function getEntity($className, $id, $primaryKey = 'id')
    {
        static $entities = [];

        if (!isset($entities[$className])) {
            $entities[$className] = [];
        }

        if (!isset($entities[$className][$id])) {
            $entities[$className][$id] = new $className();
            $reflectionClass = new \ReflectionClass($className);
            $method = $reflectionClass->getProperty($primaryKey);
            $method->setAccessible(true);
            $method->setValue($entities[$className][$id], $id);
        }

        return $entities[$className][$id];
    }
}
