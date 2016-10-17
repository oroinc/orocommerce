<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Form\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
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

    public function setUp()
    {
        $this->registry = $this->getMock(ManagerRegistry::class);

        $this->scopeManager = $this->getMockBuilder(ScopeManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new VisibilityPostSetDataListener($this->registry, $this->scopeManager);
    }

    public function testOnPostSetData()
    {
        // visibility target entity
        $product = $this->getEntity(Product::class, ['id' => 1]);

        // configure scope manager behaviour
        $visibilityForAllCriteria = new ScopeCriteria([]);
        $this->scopeManager
            ->expects($this->exactly(3))
            ->method('getCriteria')
            ->willReturnOnConsecutiveCalls([
                [ProductVisibility::PRODUCT_VISIBILITY, [], $visibilityForAllCriteria],
                [AccountGroupProductVisibility::ACCOUNT_GROUP_PRODUCT_VISIBILITY, [], new ScopeCriteria([])],
                [AccountProductVisibility::ACCOUNT_PRODUCT_VISIBILITY, [], new ScopeCriteria([])],
            ]);

        // configure database queries results
        $em = $this->getMock(EntityManagerInterface::class);
        $this->registry->method('getManagerForClass')->willReturn($em);
        $productVisibilityRepository = $this->getMock(VisibilityRepositoryInterface::class);
        $productVisibilityRepository
            ->expects($this->once())
            ->method('findByScopeCriteriaForTarget')
            ->with($visibilityForAllCriteria)
            ->willReturn([]);
        $accountProductVisibilityRepository = $this->getMock(VisibilityRepositoryInterface::class);
        $accountGroupProductVisibilityRepository = $this->getMock(VisibilityRepositoryInterface::class);
        $em->method('getRepositoryForClass')
            ->willReturnMap([
                [ProductVisibility::class, $productVisibilityRepository],
                [AccountProductVisibility::class, $accountProductVisibilityRepository],
                [AccountGroupProductVisibility::class, $accountGroupProductVisibilityRepository]
            ]);

        // configure root form behaviour
        $form = $this->getMock(FormInterface::class);
        $form->method('getData')->willReturn($product);
        $formConfig = $this->getMock(FormConfigInterface::class);
        $formConfig->method('getOption')
            ->willReturnMap([
                ['allClass', null, ProductVisibility::class],
                ['accountClass', null, AccountProductVisibility::class],
                ['accountGroupClass', null, AccountGroupProductVisibility::class],
            ]);
        $form->method('getConfig')->willReturn($formConfig);

        $allForm = $this->getMock(FormInterface::class);
        $accountForm = $this->getMock(FormInterface::class);
        $accountGroupForm = $this->getMock(FormInterface::class);

        $form->method('get')->willReturnMap([
            ['all', $allForm],
            ['account', $accountForm],
            ['accountGroup', $accountGroupForm]
        ]);

        $event = new FormEvent($form, []);
        $this->listener->onPostSetData($event);
    }

//    protected function getFormMock(array $options)
}
