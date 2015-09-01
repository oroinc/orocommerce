<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Type;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use OroB2B\Bundle\AccountBundle\Entity\Repository\AccountUserRoleRepository;
use OroB2B\Bundle\AccountBundle\Form\Type\AccountUserRoleSelectType;
use OroB2B\Bundle\AccountBundle\Form\Type\AccountUserType;
use OroB2B\Bundle\AccountBundle\Form\Type\FrontendAccountUserRoleSelectType;
use OroB2B\Bundle\AccountBundle\Form\Type\FrontendAccountUserType;
use OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Type\Stub\EntitySelectTypeStub;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Validator\Validation;

use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType as AccountSelectTypeStub;
use Oro\Bundle\FormBundle\Form\Type\OroDateType;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Type\Stub\EntityType;

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
        /** @var $query AbstractQuery | \PHPUnit_Framework_MockObject_MockObject */
        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->getMock();
        $query->expects($this->any())->method('execute')->willReturn($this->getRoles());
        /** @var $qb QueryBuilder | \PHPUnit_Framework_MockObject_MockObject */
        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $qb->expects($this->any())->method('getQuery')->willReturn($query);
        $this->securityFacade->expects($this->any())->method('getLoggedUser')->willReturn($user);
        /** @var $registry Registry | \PHPUnit_Framework_MockObject_MockObject */
        $registry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();
        /** @var $repo AccountUserRoleRepository | \PHPUnit_Framework_MockObject_MockObject */
        $repo = $this->getMockBuilder('OroB2B\Bundle\AccountBundle\Entity\Repository\AccountUserRoleRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->any())
            ->method('getAvailableRolesByAccountUserQueryBuilder')
            ->with($user)
            ->willReturn($qb);
        /** @var $em ObjectManager | \PHPUnit_Framework_MockObject_MockObject */
        $em = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $em->expects($this->any())
            ->method('getRepository')
            ->with('OroB2BAccountBundle:AccountUserRole')
            ->willReturn($repo);
        $registry->expects($this->any())->method('getManager')->willReturn($em);

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
     */
    public function testSubmit(AccountUser $defaultData, array $submittedData, AccountUser $expectedData)
    {
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
