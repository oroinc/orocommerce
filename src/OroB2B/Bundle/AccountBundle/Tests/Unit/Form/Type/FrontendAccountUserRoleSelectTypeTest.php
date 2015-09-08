<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Type;

use Symfony\Bridge\Doctrine\Form\ChoiceList\ORMQueryBuilderLoader;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\AccountUserRole;
use OroB2B\Bundle\AccountBundle\Entity\Repository\AccountUserRoleRepository;
use OroB2B\Bundle\AccountBundle\Form\Type\AccountUserRoleSelectType;
use OroB2B\Bundle\AccountBundle\Form\Type\FrontendAccountUserRoleSelectType;

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

    /** @var QueryBuilder */
    protected $qb;

    protected function setUp()
    {
        $account = $this->createAccount(1, 'account');
        $user = new AccountUser();
        $user->setAccount($account);
        $this->qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
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
            ->willReturn($this->qb);
        /** @var $em ObjectManager | \PHPUnit_Framework_MockObject_MockObject */
        $em = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $em->expects($this->any())
            ->method('getRepository')
            ->with('OroB2BAccountBundle:AccountUserRole')
            ->willReturn($repo);
        $this->registry->expects($this->any())->method('getManagerForClass')->willReturn($em);
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

    public function testConfigureOptions()
    {
        /** @var $resolver OptionsResolver | \PHPUnit_Framework_MockObject_MockObject */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setNormalizer')
            ->with($this->isType('string'), $this->isInstanceOf('\Closure'))
            ->willReturnCallback(function ($type, $closure) {
                $this->assertEquals('loader', $type);
                $this->assertEquals($closure(), new ORMQueryBuilderLoader($this->qb));
            });
        $this->formType->configureOptions($resolver);
    }

    public function testNullUser()
    {
        /** @var $securityFacade SecurityFacade | \PHPUnit_Framework_MockObject_MockObject */
        $securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();
        $securityFacade->expects($this->once())->method('getLoggedUser')->willReturn(null);
        /** @var $resolver OptionsResolver | \PHPUnit_Framework_MockObject_MockObject */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $roleFormType=new FrontendAccountUserRoleSelectType($securityFacade, $this->registry);
        $roleFormType->configureOptions($resolver);
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
