<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Form\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Form\FormScopeCriteriaResolver;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use Oro\Bundle\ScopeBundle\Tests\Unit\Stub\StubScope;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\Repository\VisibilityRepositoryInterface;
use Oro\Bundle\VisibilityBundle\Form\EventListener\VisibilityFormFieldDataProvider;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormInterface;

class VisibilityFormFieldDataProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var ScopeManager|\PHPUnit\Framework\MockObject\MockObject */
    private $scopeManager;

    /** @var FormScopeCriteriaResolver|\PHPUnit\Framework\MockObject\MockObject */
    private $formScopeCriteriaResolver;

    /** @var VisibilityFormFieldDataProvider */
    private $dataProvider;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->scopeManager = $this->createMock(ScopeManager::class);
        $this->formScopeCriteriaResolver = $this->createMock(FormScopeCriteriaResolver::class);

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
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->any())
            ->method('getData')
            ->willReturn($product);
        $formConfig = $this->createMock(FormConfigInterface::class);
        $rootScope = new Scope();
        $formConfig->expects($this->any())
            ->method('getOption')
            ->willReturnMap([
                ['allClass', null, ProductVisibility::class],
                ['scope', null, $rootScope],
            ]);
        $form->expects($this->any())
            ->method('getConfig')
            ->willReturn($formConfig);
        $allForm = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('get')
            ->with('all')
            ->willReturn($allForm);

        $this->formScopeCriteriaResolver->expects($this->once())
            ->method('resolve')
            ->with($allForm, ProductVisibility::VISIBILITY_TYPE)
            ->willReturn(new ScopeCriteria([], $this->createMock(ClassMetadataFactory::class)));

        // configure database queries results
        $visibility = $this->getEntity(
            ProductVisibility::class,
            [
                'id' => 1,
                'visibility' => 'visible',
                'scope' => new StubScope(['customerGroup' => null, 'customer' => null]),
            ]
        );

        $em = $this->createMock(EntityManagerInterface::class);
        $repository = $this->createMock(VisibilityRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('findByScopeCriteriaForTarget')
            ->willReturn([$visibility]);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($repository);
        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);

        $actual = $this->dataProvider->findFormFieldData($form, 'all');
        $this->assertEquals($visibility, $actual);
    }

    public function testFindCustomerGroupFormFieldData()
    {
        // visibility target entity
        $product = $this->getEntity(Product::class, ['id' => 1]);

        // configure form behaviour
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->any())
            ->method('getData')
            ->willReturn($product);
        $formConfig = $this->createMock(FormConfigInterface::class);
        $rootScope = new Scope();
        $formConfig->expects($this->any())
            ->method('getOption')
            ->willReturnMap([
                ['customerGroupClass', null, CustomerGroupProductVisibility::class],
                ['scope', null, $rootScope],
            ]);
        $form->expects($this->any())
            ->method('getConfig')
            ->willReturn($formConfig);
        $customerGroupForm = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('get')
            ->with('customerGroup')
            ->willReturn($customerGroupForm);

        $this->formScopeCriteriaResolver->expects($this->once())
            ->method('resolve')
            ->with($customerGroupForm, CustomerGroupProductVisibility::VISIBILITY_TYPE)
            ->willReturn(new ScopeCriteria([], $this->createMock(ClassMetadataFactory::class)));

        // configure database queries results
        $visibility1 = $this->getEntity(
            CustomerGroupProductVisibility::class,
            [
                'id' => 1,
                'visibility' => 'visible',
                'scope' => new StubScope(
                    ['customerGroup' => $this->getEntity(CustomerGroup::class, ['id' => 2]), 'customer' => null]
                ),
            ]
        );
        $visibility2 = $this->getEntity(
            CustomerGroupProductVisibility::class,
            [
                'id' => 2,
                'visibility' => 'hidden',
                'scope' => new StubScope(
                    ['customerGroup' => $this->getEntity(CustomerGroup::class, ['id' => 4]), 'customer' => null]
                ),
            ]
        );
        $em = $this->createMock(EntityManagerInterface::class);
        $repository = $this->createMock(VisibilityRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('findByScopeCriteriaForTarget')
            ->willReturn([$visibility1, $visibility2]);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($repository);
        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);

        $expected = [2 => $visibility1, 4 => $visibility2];

        $actual = $this->dataProvider->findFormFieldData($form, 'customerGroup');
        $this->assertEquals($expected, $actual);
    }

    public function testFindCustomerFormFieldData()
    {
        // visibility target entity
        $product = $this->getEntity(Product::class, ['id' => 1]);

        // configure form behaviour
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->any())
            ->method('getData')
            ->willReturn($product);
        $formConfig = $this->createMock(FormConfigInterface::class);
        $rootScope = new Scope();
        $formConfig->expects($this->any())
            ->method('getOption')
            ->willReturnMap([
                ['customerClass', null, CustomerProductVisibility::class],
                ['scope', null, $rootScope],
            ]);
        $form->expects($this->any())
            ->method('getConfig')
            ->willReturn($formConfig);
        $customerForm = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('get')
            ->with('customer')
            ->willReturn($customerForm);

        $this->formScopeCriteriaResolver->expects($this->once())
            ->method('resolve')
            ->with($customerForm, CustomerProductVisibility::VISIBILITY_TYPE)
            ->willReturn(new ScopeCriteria([], $this->createMock(ClassMetadataFactory::class)));

        // configure database queries results
        $visibility1 = $this->getEntity(
            CustomerProductVisibility::class,
            [
                'id' => 1,
                'visibility' => 'visible',
                'scope' => new StubScope(
                    ['customer' => $this->getEntity(Customer::class, ['id' => 2]), 'customerGroup' => null]
                ),
            ]
        );
        $visibility2 = $this->getEntity(
            CustomerProductVisibility::class,
            [
                'id' => 2,
                'visibility' => 'hidden',
                'scope' => new StubScope(
                    ['customer' => $this->getEntity(Customer::class, ['id' => 4]), 'customerGroup' => null]
                ),
            ]
        );
        $em = $this->createMock(EntityManagerInterface::class);
        $repository = $this->createMock(VisibilityRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('findByScopeCriteriaForTarget')
            ->willReturn([$visibility1, $visibility2]);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($repository);
        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);

        $expected = [2 => $visibility1, 4 => $visibility2];

        $actual = $this->dataProvider->findFormFieldData($form, 'customer');
        $this->assertEquals($expected, $actual);
    }

    public function testCreateFormFieldData()
    {
        $product = new Product();
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->any())
            ->method('getData')
            ->willReturn($product);
        $formConfig = $this->createMock(FormConfigInterface::class);
        $rootScope = new Scope();
        $formConfig->expects($this->any())
            ->method('hasOption')
            ->with('scope')
            ->willReturn(true);
        $formConfig->expects($this->any())
            ->method('getOption')
            ->willReturnMap([
                ['customerClass', null, CustomerProductVisibility::class],
                ['scope', null, $rootScope],
            ]);
        $form->expects($this->any())
            ->method('getConfig')
            ->willReturn($formConfig);
        $this->scopeManager->expects($this->once())
            ->method('getCriteriaByScope')
            ->with($rootScope, 'customer_product_visibility')
            ->willReturn(new ScopeCriteria(
                ['customer' => $this->getEntity(Customer::class, ['id' => 3])],
                $this->createMock(ClassMetadataFactory::class)
            ));

        $fieldData = new Customer();
        $scope = new Scope();
        $this->scopeManager->expects($this->once())
            ->method('findOrCreate')
            ->with('customer_product_visibility', ['customer' => $fieldData])
            ->willReturn($scope);

        $actual = $this->dataProvider->createFormFieldData($form, 'customer', $fieldData);
        $expected = (new CustomerProductVisibility())->setProduct($product)->setScope($scope);
        $this->assertEquals($expected, $actual);
    }
}
