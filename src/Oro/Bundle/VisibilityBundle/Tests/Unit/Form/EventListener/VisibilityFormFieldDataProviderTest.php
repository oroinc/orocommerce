<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Form\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\AccountGroup;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Form\FormScopeCriteriaResolver;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use Oro\Bundle\ScopeBundle\Tests\Unit\Stub\StubScope;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountGroupProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\Repository\VisibilityRepositoryInterface;
use Oro\Bundle\VisibilityBundle\Form\EventListener\VisibilityFormFieldDataProvider;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormInterface;

class VisibilityFormFieldDataProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var VisibilityFormFieldDataProvider
     */
    protected $dataProvider;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var ScopeManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $scopeManager;

    /**
     * @var FormScopeCriteriaResolver|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $formScopeCriteriaResolver;

    public function setUp()
    {
        $this->registry = $this->getMock(ManagerRegistry::class);

        $this->scopeManager = $this->getMockBuilder(ScopeManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->formScopeCriteriaResolver = $this->getMockBuilder(FormScopeCriteriaResolver::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->dataProvider = new VisibilityFormFieldDataProvider(
            $this->registry,
            $this->scopeManager,
            $this->formScopeCriteriaResolver
        );
    }

    public function testFindAllFormFieldData()
    {
        // visibility target entity
        $product = $this->getEntity(Product::class, ['id' => 1]);

        // configure form behaviour
        $form = $this->getMock(FormInterface::class);
        $form->method('getData')->willReturn($product);
        $formConfig = $this->getMock(FormConfigInterface::class);
        $rootScope = new Scope();
        $formConfig->method('getOption')
            ->willReturnMap(
                [
                    ['allClass', null, ProductVisibility::class],
                    ['scope', null, $rootScope],
                ]
            );
        $form->method('getConfig')->willReturn($formConfig);
        $allForm = $this->getMock(FormInterface::class);
        $form->expects($this->once())->method('get')->with('all')->willReturn($allForm);

        $this->formScopeCriteriaResolver->expects($this->once())
            ->method('resolve')
            ->with($allForm, ProductVisibility::VISIBILITY_TYPE)
            ->willReturn(new ScopeCriteria([]));

        // configure database queries results
        $visibility = $this->getEntity(
            ProductVisibility::class,
            [
                'id' => 1,
                'visibility' => 'visible',
                'scope' => new StubScope(['accountGroup' => null, 'account' => null]),
            ]
        );

        $em = $this->getMock(EntityManagerInterface::class);
        $repository = $this->getMock(VisibilityRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('findByScopeCriteriaForTarget')
            ->willReturn([$visibility]);
        $em->method('getRepository')->willReturn($repository);
        $this->registry->method('getManagerForClass')->willReturn($em);

        $actual = $this->dataProvider->findFormFieldData($form, 'all');
        $this->assertEquals($visibility, $actual);
    }

    public function testFindAccountGroupFormFieldData()
    {
        // visibility target entity
        $product = $this->getEntity(Product::class, ['id' => 1]);

        // configure form behaviour
        $form = $this->getMock(FormInterface::class);
        $form->method('getData')->willReturn($product);
        $formConfig = $this->getMock(FormConfigInterface::class);
        $rootScope = new Scope();
        $formConfig->method('getOption')
            ->willReturnMap(
                [
                    ['accountGroupClass', null, AccountGroupProductVisibility::class],
                    ['scope', null, $rootScope],
                ]
            );
        $form->method('getConfig')->willReturn($formConfig);
        $accountGroupForm = $this->getMock(FormInterface::class);
        $form->expects($this->once())->method('get')->with('accountGroup')->willReturn($accountGroupForm);

        $this->formScopeCriteriaResolver->expects($this->once())
            ->method('resolve')
            ->with($accountGroupForm, AccountGroupProductVisibility::VISIBILITY_TYPE)
            ->willReturn(new ScopeCriteria([]));

        // configure database queries results
        $visibility1 = $this->getEntity(
            AccountGroupProductVisibility::class,
            [
                'id' => 1,
                'visibility' => 'visible',
                'scope' => new StubScope(
                    ['accountGroup' => $this->getEntity(AccountGroup::class, ['id' => 2]), 'account' => null]
                ),
            ]
        );
        $visibility2 = $this->getEntity(
            AccountGroupProductVisibility::class,
            [
                'id' => 2,
                'visibility' => 'hidden',
                'scope' => new StubScope(
                    ['accountGroup' => $this->getEntity(AccountGroup::class, ['id' => 4]), 'account' => null]
                ),
            ]
        );
        $em = $this->getMock(EntityManagerInterface::class);
        $repository = $this->getMock(VisibilityRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('findByScopeCriteriaForTarget')
            ->willReturn([$visibility1, $visibility2]);
        $em->method('getRepository')->willReturn($repository);
        $this->registry->method('getManagerForClass')->willReturn($em);

        $expected = [2 => $visibility1, 4 => $visibility2];

        $actual = $this->dataProvider->findFormFieldData($form, 'accountGroup');
        $this->assertEquals($expected, $actual);
    }

    public function testFindAccountFormFieldData()
    {
        // visibility target entity
        $product = $this->getEntity(Product::class, ['id' => 1]);

        // configure form behaviour
        $form = $this->getMock(FormInterface::class);
        $form->method('getData')->willReturn($product);
        $formConfig = $this->getMock(FormConfigInterface::class);
        $rootScope = new Scope();
        $formConfig->method('getOption')
            ->willReturnMap(
                [
                    ['accountClass', null, AccountProductVisibility::class],
                    ['scope', null, $rootScope],
                ]
            );
        $form->method('getConfig')->willReturn($formConfig);
        $accountForm = $this->getMock(FormInterface::class);
        $form->expects($this->once())->method('get')->with('account')->willReturn($accountForm);

        $this->formScopeCriteriaResolver->expects($this->once())
            ->method('resolve')
            ->with($accountForm, AccountProductVisibility::VISIBILITY_TYPE)
            ->willReturn(new ScopeCriteria([]));

        // configure database queries results
        $visibility1 = $this->getEntity(
            AccountProductVisibility::class,
            [
                'id' => 1,
                'visibility' => 'visible',
                'scope' => new StubScope(
                    ['account' => $this->getEntity(Account::class, ['id' => 2]), 'accountGroup' => null]
                ),
            ]
        );
        $visibility2 = $this->getEntity(
            AccountProductVisibility::class,
            [
                'id' => 2,
                'visibility' => 'hidden',
                'scope' => new StubScope(
                    ['account' => $this->getEntity(Account::class, ['id' => 4]), 'accountGroup' => null]
                ),
            ]
        );
        $em = $this->getMock(EntityManagerInterface::class);
        $repository = $this->getMock(VisibilityRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('findByScopeCriteriaForTarget')
            ->willReturn([$visibility1, $visibility2]);
        $em->method('getRepository')->willReturn($repository);
        $this->registry->method('getManagerForClass')->willReturn($em);

        $expected = [2 => $visibility1, 4 => $visibility2];

        $actual = $this->dataProvider->findFormFieldData($form, 'account');
        $this->assertEquals($expected, $actual);
    }

    public function testCreateFormFieldData()
    {
        $product = new Product();
        $form = $this->getMock(FormInterface::class);
        $form->method('getData')->willReturn($product);
        $formConfig = $this->getMock(FormConfigInterface::class);
        $rootScope = new Scope();
        $formConfig->method('hasOption')->with('scope')->willReturn(true);
        $formConfig->method('getOption')
            ->willReturnMap(
                [
                    ['accountClass', null, AccountProductVisibility::class],
                    ['scope', null, $rootScope],
                ]
            );
        $form->method('getConfig')->willReturn($formConfig);
        $this->scopeManager->expects($this->once())
            ->method('getCriteriaByScope')
            ->with($rootScope, 'account_product_visibility')
            ->willReturn(new ScopeCriteria(['account' => $this->getEntity(Account::class, ['id' => 3])]));

        $fieldData = new Account();
        $scope = new Scope();
        $this->scopeManager->expects($this->once())
            ->method('findOrCreate')
            ->with('account_product_visibility', ['account' => $fieldData])
            ->willReturn($scope);

        $actual = $this->dataProvider->createFormFieldData($form, 'account', $fieldData);
        $expected = (new AccountProductVisibility())->setProduct($product)->setScope($scope);
        $this->assertEquals($expected, $actual);
    }
}
