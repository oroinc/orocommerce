<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Type;

use Symfony\Component\Validator\ConstraintValidatorInterface;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;

abstract class AbstractTest extends FormIntegrationTestCase
{
    use EntityTrait;

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
        return $this->getUniqueEntity($id, 'OroB2B\Bundle\AccountBundle\Entity\AccountUser', [
            'id' => $id,
            'firstName' => 'FirstName',
            'lastName' => 'LastName',
            'email' => 'test@example.com',
            'account' => $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\Account', ['id' => $id]),
            'organization' => $this->getEntity('Oro\Bundle\OrganizationBundle\Entity\Organization', ['id' => $id]),
            'roles' => [$this->getEntity('Symfony\Component\Security\Core\Role\Role')],
        ]);
    }
}
