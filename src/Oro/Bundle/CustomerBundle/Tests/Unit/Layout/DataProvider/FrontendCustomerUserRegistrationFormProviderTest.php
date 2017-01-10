<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Role\RoleInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\Repository\CustomerUserRoleRepository;
use Oro\Bundle\CustomerBundle\Form\Type\FrontendCustomerUserRegistrationType;
use Oro\Bundle\CustomerBundle\Layout\DataProvider\FrontendCustomerUserRegistrationFormProvider;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;

use Oro\Component\Testing\Unit\EntityTrait;

class FrontendCustomerUserRegistrationFormProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var FrontendCustomerUserRegistrationFormProvider */
    protected $dataProvider;

    /** @var FormFactoryInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $formFactory;

    /** @var CustomerUserRoleRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $roleRepository;

    /** @var ObjectRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $userRepository;

    /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var WebsiteManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $websiteManager;

    /** @var UrlGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $router;

    protected function setUp()
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);

        $this->roleRepository = $this->createMock(CustomerUserRoleRepository::class);

        $objectManager = $this->createMock(ObjectManager::class);
        $objectManager->expects($this->any())
            ->method('getRepository')
            ->with('OroCustomerBundle:CustomerUserRole')
            ->willReturn($this->roleRepository);

        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry->expects($this->any())
            ->method('getManagerForClass')
            ->with('OroCustomerBundle:CustomerUserRole')
            ->willReturn($objectManager);

        $this->configManager = $this->createMock(ConfigManager::class);
        $this->websiteManager = $this->createMock(WebsiteManager::class);

        $this->userRepository = $this->createMock(ObjectRepository::class);

        $userManager = $this->createMock(UserManager::class);
        $userManager->expects($this->any())
            ->method('getRepository')
            ->willReturn($this->userRepository);

        $this->router = $this->createMock(UrlGeneratorInterface::class);

        $this->dataProvider = new FrontendCustomerUserRegistrationFormProvider(
            $this->formFactory,
            $managerRegistry,
            $this->configManager,
            $this->websiteManager,
            $userManager,
            $this->router
        );
    }

    public function testGetRegisterFormView()
    {
        $action = 'form_action';

        $defaultOwnerId = 1;
        $defaultRole = $this->createMock(RoleInterface::class);

        $organization = $this->getEntity(Organization::class);
        $website = $this->getEntity(Website::class, ['organization' => $organization]);
        $owner = $this->getEntity(User::class);

        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with('oro_customer.default_customer_owner')
            ->willReturn($defaultOwnerId);

        $this->websiteManager
            ->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn($website);

        $this->roleRepository
            ->expects($this->once())
            ->method('getDefaultCustomerUserRoleByWebsite')
            ->with($website)
            ->willReturn($defaultRole);

        $this->userRepository
            ->expects($this->once())
            ->method('find')
            ->with($defaultOwnerId)
            ->willReturn($owner);

        $formView = $this->createMock(FormView::class);

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('createView')
            ->willReturn($formView);

        $this->formFactory
            ->expects($this->once())
            ->method('create')
            ->with(FrontendCustomerUserRegistrationType::NAME)
            ->willReturn($form);

        $this->router
            ->expects($this->once())
            ->method('generate')
            ->with(FrontendCustomerUserRegistrationFormProvider::ACCOUNT_USER_REGISTER_ROUTE_NAME, [])
            ->willReturn($action);

        $actual = $this->dataProvider->getRegisterFormView();

        $this->assertInstanceOf(FormView::class, $actual);
    }

    /**
     * @expectedException        \RuntimeException
     * @expectedExceptionMessage Application Owner is empty
     */
    public function testGetRegisterFormViewOwnerEmpty()
    {
        $defaultOwnerId = false;

        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with('oro_customer.default_customer_owner')
            ->willReturn($defaultOwnerId);

        $this->websiteManager
            ->expects($this->never())
            ->method('getCurrentWebsite');

        $this->roleRepository
            ->expects($this->never())
            ->method('getDefaultCustomerUserRoleByWebsite');

        $this->userRepository
            ->expects($this->never())
            ->method('find');

        $this->formFactory
            ->expects($this->never())
            ->method('create');

        $this->router
            ->expects($this->never())
            ->method('generate');

        $this->dataProvider->getRegisterFormView();
    }

    /**
     * @expectedException        \RuntimeException
     * @expectedExceptionMessage Website is empty
     */
    public function testGetRegisterFormViewWebsiteEmpty()
    {
        $defaultOwnerId = 1;
        $website = false;

        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with('oro_customer.default_customer_owner')
            ->willReturn($defaultOwnerId);

        $this->websiteManager
            ->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn($website);

        $this->roleRepository
            ->expects($this->never())
            ->method('getDefaultCustomerUserRoleByWebsite');

        $this->userRepository
            ->expects($this->never())
            ->method('find');

        $this->formFactory
            ->expects($this->never())
            ->method('create');

        $this->router
            ->expects($this->never())
            ->method('generate');

        $this->dataProvider->getRegisterFormView();
    }

    /**
     * @expectedException        \RuntimeException
     * @expectedExceptionMessage Website organization is empty
     */
    public function testGetRegisterFormViewOrganizationEmpty()
    {

        $defaultOwnerId = 1;
        $website = $this->getEntity(Website::class);

        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with('oro_customer.default_customer_owner')
            ->willReturn($defaultOwnerId);

        $this->websiteManager
            ->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn($website);

        $this->roleRepository
            ->expects($this->never())
            ->method('getDefaultCustomerUserRoleByWebsite');

        $this->userRepository
            ->expects($this->never())
            ->method('find');

        $this->formFactory
            ->expects($this->never())
            ->method('create');

        $this->router
            ->expects($this->never())
            ->method('generate');

        $this->dataProvider->getRegisterFormView();
    }

    /**
     * @expectedException        \RuntimeException
     * @expectedExceptionMessage Role "ROLE_USER" was not found
     */
    public function testGetRegisterFormViewEmptyRole()
    {
        $defaultOwnerId = 1;
        $defaultRole = false;
        $organization = $this->getEntity(Organization::class);
        $website = $this->getEntity(Website::class, ['organization' => $organization]);

        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with('oro_customer.default_customer_owner')
            ->willReturn($defaultOwnerId);

        $this->websiteManager
            ->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn($website);

        $this->roleRepository
            ->expects($this->once())
            ->method('getDefaultCustomerUserRoleByWebsite')
            ->with($website)
            ->willReturn($defaultRole);

        $this->userRepository
            ->expects($this->never())
            ->method('find');

        $this->formFactory
            ->expects($this->never())
            ->method('create');

        $this->router
            ->expects($this->never())
            ->method('generate');

        $this->dataProvider->getRegisterFormView();
    }

    public function testGetRegisterForm()
    {
        $action = 'form_action';

        $defaultOwnerId = 1;
        $defaultRole = $this->createMock(RoleInterface::class);

        $organization = $this->getEntity(Organization::class);
        $website = $this->getEntity(Website::class, ['organization' => $organization]);
        $owner = $this->getEntity(User::class);

        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with('oro_customer.default_customer_owner')
            ->willReturn($defaultOwnerId);

        $this->websiteManager
            ->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn($website);

        $this->roleRepository
            ->expects($this->once())
            ->method('getDefaultCustomerUserRoleByWebsite')
            ->with($website)
            ->willReturn($defaultRole);

        $this->userRepository
            ->expects($this->once())
            ->method('find')
            ->with($defaultOwnerId)
            ->willReturn($owner);

        $form = $this->createMock(FormInterface::class);

        $this->formFactory
            ->expects($this->once())
            ->method('create')
            ->with(FrontendCustomerUserRegistrationType::NAME)
            ->willReturn($form);

        $this->router
            ->expects($this->once())
            ->method('generate')
            ->with(FrontendCustomerUserRegistrationFormProvider::ACCOUNT_USER_REGISTER_ROUTE_NAME, [])
            ->willReturn($action);

        $actual = $this->dataProvider->getRegisterForm();

        $this->assertInstanceOf(FormInterface::class, $actual);
    }

    /**
     * @expectedException        \RuntimeException
     * @expectedExceptionMessage Application Owner is empty
     */
    public function testGetRegisterFormOwnerEmpty()
    {
        $defaultOwnerId = false;

        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with('oro_customer.default_customer_owner')
            ->willReturn($defaultOwnerId);

        $this->websiteManager
            ->expects($this->never())
            ->method('getCurrentWebsite');

        $this->roleRepository
            ->expects($this->never())
            ->method('getDefaultCustomerUserRoleByWebsite');

        $this->userRepository
            ->expects($this->never())
            ->method('find');

        $this->formFactory
            ->expects($this->never())
            ->method('create');

        $this->router
            ->expects($this->never())
            ->method('generate');

        $this->dataProvider->getRegisterForm();
    }

    /**
     * @expectedException        \RuntimeException
     * @expectedExceptionMessage Website is empty
     */
    public function testGetRegisterFormWebsiteEmpty()
    {
        $defaultOwnerId = 1;
        $website = false;

        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with('oro_customer.default_customer_owner')
            ->willReturn($defaultOwnerId);

        $this->websiteManager
            ->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn($website);

        $this->roleRepository
            ->expects($this->never())
            ->method('getDefaultCustomerUserRoleByWebsite');

        $this->userRepository
            ->expects($this->never())
            ->method('find');

        $this->formFactory
            ->expects($this->never())
            ->method('create');

        $this->router
            ->expects($this->never())
            ->method('generate');

        $this->dataProvider->getRegisterForm();
    }

    /**
     * @expectedException        \RuntimeException
     * @expectedExceptionMessage Website organization is empty
     */
    public function testGetRegisterFormOrganizationEmpty()
    {

        $defaultOwnerId = 1;
        $website = $this->getEntity(Website::class);

        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with('oro_customer.default_customer_owner')
            ->willReturn($defaultOwnerId);

        $this->websiteManager
            ->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn($website);

        $this->roleRepository
            ->expects($this->never())
            ->method('getDefaultCustomerUserRoleByWebsite');

        $this->userRepository
            ->expects($this->never())
            ->method('find');

        $this->formFactory
            ->expects($this->never())
            ->method('create');

        $this->router
            ->expects($this->never())
            ->method('generate');

        $this->dataProvider->getRegisterForm();
    }

    /**
     * @expectedException        \RuntimeException
     * @expectedExceptionMessage Role "ROLE_USER" was not found
     */
    public function testGetRegisterFormEmptyRole()
    {
        $defaultOwnerId = 1;
        $defaultRole = false;
        $organization = $this->getEntity(Organization::class);
        $website = $this->getEntity(Website::class, ['organization' => $organization]);

        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with('oro_customer.default_customer_owner')
            ->willReturn($defaultOwnerId);

        $this->websiteManager
            ->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn($website);

        $this->roleRepository
            ->expects($this->once())
            ->method('getDefaultCustomerUserRoleByWebsite')
            ->with($website)
            ->willReturn($defaultRole);

        $this->userRepository
            ->expects($this->never())
            ->method('find');

        $this->formFactory
            ->expects($this->never())
            ->method('create');

        $this->router
            ->expects($this->never())
            ->method('generate');

        $this->dataProvider->getRegisterForm();
    }
}
