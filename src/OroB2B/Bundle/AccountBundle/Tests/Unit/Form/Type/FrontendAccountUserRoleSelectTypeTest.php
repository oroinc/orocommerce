<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Type;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\AccountUserRole;
use OroB2B\Bundle\AccountBundle\Entity\Repository\AccountUserRoleRepository;
use OroB2B\Bundle\AccountBundle\Form\Type\AccountUserRoleSelectType;
use OroB2B\Bundle\AccountBundle\Form\Type\FrontendAccountUserRoleSelectType;

use OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Type\Stub\AclExtensionStub;
use OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Type\Stub\RoleSelectStub;
use Symfony\Bridge\Doctrine\Form\ChoiceList\ORMQueryBuilderLoader;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Validation;

class FrontendAccountUserRoleSelectTypeTest extends FormIntegrationTestCase
{
    /**
     * @var FrontendAccountUserRoleSelectType
     */
    protected $formType;

    /** @var SecurityFacade | \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var $registry Registry | \PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    protected function setUp()
    {

        $account = $this->createAccount(1, 'account');
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
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();
        $this->securityFacade->expects($this->any())->method('getLoggedUser')->willReturn($user);
        $this->registry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
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
        $this->registry->expects($this->any())->method('getManager')->willReturn($em);
        $this->formType = new FrontendAccountUserRoleSelectType($this->securityFacade, $this->registry);
        parent::setUp();
    }

    public function testGetRegistry()
    {
        $this->assertEquals($this->formType->getRegistry(), $this->registry);
    }

    public function testGetName()
    {
        $this->assertEquals(FrontendAccountUserRoleSelectType::NAME, $this->formType->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals(AccountUserRoleSelectType::NAME, $this->formType->getParent());
    }

    /**
     * @dataProvider submitProvider
     *
     * @param AccountUserRole $defaultData
     * @param array $submittedData
     * @param AccountUserRole $expectedData
     */
    public function testSubmit(AccountUserRole $defaultData, array $submittedData, AccountUserRole $expectedData)
    {
        $this->markTestSkipped('Test is not finished');
        $form = $this->factory->create($this->formType, $defaultData);

        $this->assertEquals($defaultData, $form->getData());
        $form->submit($submittedData);
        $result = $form->isValid();
        $this->assertTrue($result);
        $this->assertEquals($expectedData, $form->getData());
    }


    public function submitProvider()
    {
        $newRole = new AccountUserRole();

        return [
            [
                'defaultData' => $newRole,
                'submittedData' => [],
                'expectedData' => $newRole
            ]
        ];
    }

    protected function getExtensions()
    {
        $accountUserRoleSelectType = new RoleSelectStub(
            $this->getRoles(),
            AccountUserRoleSelectType::NAME
        );
        return [
            new PreloadedExtension(
                [
                    AccountUserRoleSelectType::NAME => $accountUserRoleSelectType
                ],
                ['choice' => [new AclExtensionStub()]]
            ),
            new ValidatorExtension(Validation::createValidator()),
        ];
    }

    public function testConfigureOptions()
    {
        /** @var $resolver OptionsResolver | \PHPUnit_Framework_MockObject_MockObject */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())->method('setOptional')->with(['loader']);
        $callback = function () {
            $qb = $this->registry->getManager()
                ->getRepository('OroB2BAccountBundle:AccountUserRole')
                ->getAvailableRolesByAccountUserQueryBuilder($this->securityFacade->getLoggedUser());
            return new ORMQueryBuilderLoader($qb);
        };
        $resolver->expects($this->once())->method('setNormalizer')->with('loader', $callback);
        $this->formType->configureOptions($resolver);
    }

    /**
     * @param int $id
     * @param string $name
     * @return Account
     */
    protected static function createAccount($id, $name)
    {
        $account = new Account();

        $reflection = new \ReflectionProperty(get_class($account), 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($account, $id);

        $account->setName($name);

        return $account;
    }


    /**
     * @return AccountUserRole[]
     */
    protected function getRoles()
    {
        return [
            1 => $this->getRole(1, 'test01'),
            2 => $this->getRole(2, 'test02')
        ];
    }

    /**
     * @param int $id
     * @param string $label
     * @return AccountUserRole
     */
    protected function getRole($id, $label)
    {
        $role = new AccountUserRole($label);

        $reflection = new \ReflectionProperty(get_class($role), 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($role, $id);

        $role->setLabel($label);

        return $role;
    }
}
