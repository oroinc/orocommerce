<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Form\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Entity\AccountGroup;
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
use Oro\Bundle\VisibilityBundle\Form\EventListener\VisibilityPostSetDataListener;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\Test\FormInterface;

class VisibilityPostSetDataListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var VisibilityPostSetDataListener|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $listener;

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

        $this->listener = new VisibilityPostSetDataListener(
            $this->registry,
            $this->scopeManager,
            $this->formScopeCriteriaResolver
        );
    }

    public function testOnPostSetData()
    {
        // visibility target entity
        $product = $this->getEntity(Product::class, ['id' => 1]);

        // configure root form behaviour
        $form = $this->getMock(FormInterface::class);
        $form->method('getData')->willReturn($product);
        $formConfig = $this->getMock(FormConfigInterface::class);
        $rootScope = new Scope();
        $formConfig->method('getOption')
            ->willReturnMap(
                [
                    ['allClass', null, ProductVisibility::class],
                    ['accountClass', null, AccountProductVisibility::class],
                    ['accountGroupClass', null, AccountGroupProductVisibility::class],
                    ['scope', null, $rootScope],
                ]
            );
        $form->method('getConfig')->willReturn($formConfig);
        $this->formScopeCriteriaResolver->expects($this->exactly(3))
            ->method('resolve')
            ->willReturn(new ScopeCriteria([]));

        // configure database queries results
        $account1 = $this->getEntity(Account::class, ['id' => 2]);
        $account2 = $this->getEntity(Account::class, ['id' => 4]);
        $accountGroup1 = $this->getEntity(AccountGroup::class, ['id' => 3]);
        $accountGroup2 = $this->getEntity(AccountGroup::class, ['id' => 5]);
        $this->setExistingVisibilityEntities(
            [
                ProductVisibility::class => [],
                AccountProductVisibility::class => [
                    [
                        'id' => 1,
                        'visibility' => 'visible',
                        'scope' => new StubScope(['account' => $account1, 'accountGroup' => null]),
                    ],
                    [
                        'id' => 2,
                        'visibility' => 'hidden',
                        'scope' => new StubScope(['account' => $account2, 'accountGroup' => null]),
                    ],
                ],
                AccountGroupProductVisibility::class => [
                    [
                        'id' => 1,
                        'visibility' => 'visible',
                        'scope' => new StubScope(['accountGroup' => $accountGroup1, 'account' => null]),
                    ],
                    [
                        'id' => 2,
                        'visibility' => 'hidden',
                        'scope' => new StubScope(['accountGroup' => $accountGroup2, 'account' => null]),
                    ],
                ],
            ]
        );

        $allForm = $this->getMock(FormInterface::class);
        $accountForm = $this->getMock(FormInterface::class);
        $accountGroupForm = $this->getMock(FormInterface::class);

        $form->method('get')->willReturnMap(
            [
                ['all', $allForm],
                ['account', $accountForm],
                ['accountGroup', $accountGroupForm],
            ]
        );

        // assert data was set
        $allForm->expects($this->once())
            ->method('setData')
            ->with('category');

        $accountGroupForm->expects($this->once())
            ->method('setData')
            ->with(
                [
                    3 => ['entity' => $accountGroup1, 'data' => ['visibility' => 'visible']],
                    5 => ['entity' => $accountGroup2, 'data' => ['visibility' => 'hidden']],
                ]
            );
        $accountForm->expects($this->once())
            ->method('setData')
            ->with(
                [
                    2 => ['entity' => $account1, 'data' => ['visibility' => 'visible']],
                    4 => ['entity' => $account2, 'data' => ['visibility' => 'hidden']],
                ]
            );

        $event = new FormEvent($form, []);
        $this->listener->onPostSetData($event);
    }

    /**
     * @param array $data
     */
    protected function setExistingVisibilityEntities(array $data)
    {
        $em = $this->getMock(EntityManagerInterface::class);
        $repositories = [];
        foreach ($data as $className => $items) {
            $repository = $this->getMock(VisibilityRepositoryInterface::class);
            $entities = [];
            foreach ($items as $item) {
                $entities[] = $this->getEntity($className, $item);
            }

            $repository
                ->expects($this->once())
                ->method('findByScopeCriteriaForTarget')
                ->willReturn($entities);
            $repositories[] = [$className, $repository];
        }
        $em->method('getRepository')->willReturnMap($repositories);
        $this->registry->method('getManagerForClass')->willReturn($em);
    }
}
