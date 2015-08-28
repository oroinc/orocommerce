<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Type;

use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Form\Type\AccountSelectType;
use OroB2B\Bundle\AccountBundle\Form\Type\AccountUserType;
use OroB2B\Bundle\AccountBundle\Form\Type\FrontendAccountUserType;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FrontendAccountUserTypeTest extends FormIntegrationTestCase
{
    /**
     * @var FrontendAccountUserType
     */
    protected $formType;

    /** @var SecurityFacade | \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    protected function setUp()
    {
        parent::setUp();
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();
        $this->formType = new FrontendAccountUserType($this->securityFacade);
    }

    public function testGetParent()
    {
        $this->assertEquals(AccountUserType::NAME, $this->formType->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals(FrontendAccountUserType::NAME, $this->formType->getName());
    }

    public function testConfigureOptions()
    {
        /** @var $resolver OptionsResolver| \PHPUnit_Framework_MockObject_MockObject */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())->method('setDefaults')->with(['skip_role_acl_check' => true]);
        $this->formType->configureOptions($resolver);
    }

    /**
     *
     */
    public function testBuildForm()
    {
        /** @var $formBuilder FormBuilderInterface | \PHPUnit_Framework_MockObject_MockObject */
        $builder = $this->getMock('Symfony\Component\Form\FormBuilderInterface');
        $accountId = 1;
        $account = $this->createAccount($accountId, 'account');
        $user = new AccountUser();
        $user->setAccount($account);
        $this->securityFacade->expects($this->once())->method('getLoggedUser')->willReturn($user);
        $builder->expects($this->once())->method('add')->with(
            'account',
            AccountSelectType::NAME,
            [
                'data' => $account,
                'empty_data' => $accountId,
            ]
        );
        $this->formType->buildForm($builder, []);
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
}
