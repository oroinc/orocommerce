<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Form\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\VisibilityBundle\Form\EventListener\VisibilityFormFieldDataProvider;
use Oro\Bundle\VisibilityBundle\Form\EventListener\VisibilityFormPostSubmitDataHandler;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Form\FormInterface;

class VisibilityFormPostSubmitDataHandlerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var VisibilityFormPostSubmitDataHandler
     */
    protected $dataHandler;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $em;

    /**
     * @var VisibilityFormFieldDataProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $fieldDataProvider;

    protected function setUp()
    {
        $this->registry = $this->getMock(ManagerRegistry::class);
        $this->em = $this->getMock(EntityManagerInterface::class);
        $this->registry->method('getManagerForClass')->willReturn($this->em);

        $this->fieldDataProvider = $this->getMockBuilder(VisibilityFormFieldDataProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->dataHandler = new VisibilityFormPostSubmitDataHandler(
            $this->registry,
            $this->fieldDataProvider
        );
    }

    public function testSaveInvalidForm()
    {
        $form = $this->getMock(FormInterface::class);
        $targetEntity = $this->getEntity(Product::class, ['id' => 1]);
        $form->method('getData')->willReturn($targetEntity);
        $form->method('isValid')->willReturn(false);

        $this->em->expects($this->never())->method('persist');
        $form->expects($this->never())->method('get');

        $this->dataHandler->saveForm($form, $targetEntity);
    }

    public function testSaveForm()
    {
        $form = $this->getMock(FormInterface::class);
        $targetEntity = $this->getEntity(Product::class, ['id' => 1]);
        $form->method('getData')->willReturn($targetEntity);
        $form->method('isValid')->willReturn(true);

        $allForm = $this->getMock(FormInterface::class);
        $allForm->method('getData')->willReturn('hidden');
        $accountForm = $this->getMock(FormInterface::class);
        $account3 = $this->getEntity(Account::class, ['id' => 3]);
        $accountForm->method('getData')->willReturn(
            [
                1 => [
                    'data' => ['visibility' => 'category'],
                    'entity' => $this->getEntity(Account::class, ['id' => 1]),
                ],
                2 => [
                    'data' => ['visibility' => 'account_group'],
                    'entity' => $this->getEntity(Account::class, ['id' => 2]),
                ],
                3 => [
                    'data' => ['visibility' => 'visible'],
                    'entity' => $account3,
                ],
            ]
        );
        $accountGroupForm = $this->getMock(FormInterface::class);
        $accountGroupForm->method('getData')->willReturn([]);

        $form->method('get')->willReturnMap(
            [
                ['all', $allForm],
                ['account', $accountForm],
                ['accountGroup', $accountGroupForm],
            ]
        );

        $productVisibility = new ProductVisibility();
        $accountProductVisibility1 = (new AccountProductVisibility())->setVisibility('hidden');
        $accountProductVisibility2 = (new AccountProductVisibility())->setVisibility('visible');
        $accountProductVisibility3 = (new AccountProductVisibility());
        $this->fieldDataProvider->method('findFormFieldData')
            ->willReturnMap(
                [
                    [$form, 'all', null],
                    [$form, 'account', [1 => $accountProductVisibility1, 2 => $accountProductVisibility2]],
                    [$form, 'accountGroup', []],
                ]
            );
        // expect new visibility entities will be created with following arguments
        $this->fieldDataProvider->method('createFormFieldData')
            ->willReturnMap(
                [
                    [$form, 'all', null, $productVisibility],
                    [$form, 'account', $account3, $accountProductVisibility3],
                ]
            );

        // assert that new visibility entity persisted when visibility value is not default
        $this->em->expects($this->at(0))
            ->method('persist')
            ->with($productVisibility);

        // assert that existing account visibility with new non default will be persisted
        $this->em->expects($this->at(1))
            ->method('persist')
            ->with($accountProductVisibility1);

        // assert that existing account visibility with new default will be remove
        $this->em->expects($this->at(2))
            ->method('remove')
            ->with($accountProductVisibility2);

        // assert that account visibility with non default will be persisted
        $this->em->expects($this->at(3))
            ->method('persist')
            ->with($accountProductVisibility3);
        $this->dataHandler->saveForm($form, $targetEntity);

        $this->assertEquals($productVisibility->getVisibility(), 'hidden');
        $this->assertEquals($accountProductVisibility1->getVisibility(), 'category');
        $this->assertEquals($accountProductVisibility3->getVisibility(), 'visible');
    }
}
