<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Forms;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;

use Doctrine\ORM\Query;

use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType as AccountSelectTypeStub;
use Oro\Bundle\FormBundle\Form\Type\OroDateType;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Type\Stub\EntityType;
use OroB2B\Bundle\AccountBundle\Form\Type\AccountUserRoleSelectType;
use OroB2B\Bundle\AccountBundle\Form\Type\AccountUserType;
use OroB2B\Bundle\AccountBundle\Form\Type\FrontendAccountUserRoleSelectType;
use OroB2B\Bundle\AccountBundle\Form\Type\FrontendAccountUserType;
use OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Type\Stub\EntitySelectTypeStub;
use OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Type\Stub\AddressCollectionTypeStub;

class FrontendAccountUserTypeTest extends AccountUserTypeTest
{
    /**
     * @var FrontendAccountUserType
     */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->formType = new FrontendAccountUserType($this->securityFacade);

        $this->factory = Forms::createFormFactoryBuilder()
            ->addExtensions($this->getExtensions())
            ->getFormFactory();
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $account = $this->getAccount(1);
        $user = new AccountUser();
        $user->setAccount($account);
        $this->securityFacade->expects($this->any())->method('getLoggedUser')->willReturn($user);

        $frontendUserRoleSelectType = new EntitySelectTypeStub(
            $this->getRoles(),
            FrontendAccountUserRoleSelectType::NAME,
            new AccountUserRoleSelectType()
        );
        $addressEntityType = new EntityType($this->getAddresses(), 'test_address_entity');
        $accountSelectType = new AccountSelectTypeStub($this->getAccounts(), 'orob2b_account_select');

        $accountUserType = new AccountUserType($this->securityFacade);
        $accountUserType->setDataClass(self::DATA_CLASS);
        $accountUserType->setAddressClass(self::ADDRESS_CLASS);

        return [
            new PreloadedExtension(
                [
                    OroDateType::NAME => new OroDateType(),
                    AccountUserType::NAME => $accountUserType,
                    FrontendAccountUserRoleSelectType::NAME => $frontendUserRoleSelectType,
                    $accountSelectType->getName() => $accountSelectType,
                    AddressCollectionTypeStub::NAME => new AddressCollectionTypeStub(),
                    $addressEntityType->getName() => $addressEntityType,
                ],
                []
            ),
            new ValidatorExtension(Validation::createValidator()),
        ];
    }

    /**
     * @dataProvider submitProvider
     *
     * @param AccountUser $defaultData
     * @param array $submittedData
     * @param AccountUser $expectedData
     * @param bool $roleGranted
     */
    public function testSubmit(
        AccountUser $defaultData,
        array $submittedData,
        AccountUser $expectedData,
        $roleGranted = true
    ) {
        $form = $this->factory->create($this->formType, $defaultData, []);

        $this->assertEquals($defaultData, $form->getData());
        $form->submit($submittedData);
        $result = $form->isValid();
        $this->assertTrue($result);
        $this->assertEquals($expectedData, $form->getData());
    }


    /**
     * @return array
     */
    public function submitProvider()
    {
        $newAccountUser = new AccountUser();

        $existingAccountUser = new AccountUser();

        $class = new \ReflectionClass($existingAccountUser);
        $prop = $class->getProperty('id');
        $prop->setAccessible(true);
        $prop->setValue($existingAccountUser, 42);

        $existingAccountUser->setFirstName('John');
        $existingAccountUser->setLastName('Doe');
        $existingAccountUser->setEmail('johndoe@example.com');
        $existingAccountUser->setPassword('123456');
        $existingAccountUser->setAccount($this->getAccount(1));
        $existingAccountUser->addAddress($this->getAddresses()[1]);

        $alteredExistingAccountUser = clone $existingAccountUser;
        $alteredExistingAccountUser->setEnabled(false);
        $alteredExistingAccountUser->setAccount($this->getAccount(2));

        $alteredExistingAccountUserWithRole = clone $alteredExistingAccountUser;
        $alteredExistingAccountUserWithRole->setRoles([$this->getRole(2, 'test02')]);

        $alteredExistingAccountUserWithAddresses = clone $alteredExistingAccountUser;
        $alteredExistingAccountUserWithAddresses->addAddress($this->getAddresses()[2]);

        return
            [
                'user without submitted data' => [
                    'defaultData' => $newAccountUser,
                    'submittedData' => [],
                    'expectedData' => $newAccountUser
                ],
                'altered existing user' => [
                    'defaultData' => $existingAccountUser,
                    'submittedData' => [
                        'firstName' => 'John',
                        'lastName' => 'Doe',
                        'email' => 'johndoe@example.com',
                        'account' => 2
                    ],
                    'expectedData' => $alteredExistingAccountUser
                ],
                'altered existing user with roles' => [
                    'defaultData' => $existingAccountUser,
                    'submittedData' => [
                        'firstName' => 'John',
                        'lastName' => 'Doe',
                        'email' => 'johndoe@example.com',
                        'account' => 2,
                        'roles' => [2]
                    ],
                    'expectedData' => $alteredExistingAccountUserWithRole,

                    'altered existing user with addresses' => [
                        'defaultData' => $existingAccountUser,
                        'submittedData' => [
                            'firstName' => 'John',
                            'lastName' => 'Doe',
                            'email' => 'johndoe@example.com',
                            'account' => 2,
                            'addresses' => [1, 2]
                        ],
                        'expectedData' => $alteredExistingAccountUserWithAddresses,
                    ]
                ]];
    }

    /**
     * Test getName
     */
    public function testGetName()
    {
        $this->assertEquals(FrontendAccountUserType::NAME, $this->formType->getName());
    }
}
