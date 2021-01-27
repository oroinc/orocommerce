<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Form\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\VisibilityBundle\Form\EventListener\VisibilityFormFieldDataProvider;
use Oro\Bundle\VisibilityBundle\Form\EventListener\VisibilityFormPostSubmitDataHandler;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Form\FormInterface;

class VisibilityFormPostSubmitDataHandlerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var VisibilityFormPostSubmitDataHandler
     */
    protected $dataHandler;

    /**
     * @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $registry;

    /**
     * @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $em;

    /**
     * @var VisibilityFormFieldDataProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $fieldDataProvider;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
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
        $form = $this->createMock(FormInterface::class);
        $targetEntity = $this->getEntity(Product::class, ['id' => 1]);
        $form->method('getData')->willReturn($targetEntity);
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(false);

        $this->em->expects($this->never())->method('persist');
        $form->expects($this->never())->method('get');

        $this->dataHandler->saveForm($form, $targetEntity);
    }

    public function testSaveForm()
    {
        $form = $this->createMock(FormInterface::class);
        $targetEntity = $this->getEntity(Product::class, ['id' => 1]);
        $form->method('getData')->willReturn($targetEntity);
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);

        $allForm = $this->createMock(FormInterface::class);
        $allForm->method('getData')->willReturn('hidden');
        $customerForm = $this->createMock(FormInterface::class);
        $customer3 = $this->getEntity(Customer::class, ['id' => 3]);
        $customerForm->method('getData')->willReturn(
            [
                1 => [
                    'data' => ['visibility' => 'category'],
                    'entity' => $this->getEntity(Customer::class, ['id' => 1]),
                ],
                2 => [
                    'data' => ['visibility' => 'customer_group'],
                    'entity' => $this->getEntity(Customer::class, ['id' => 2]),
                ],
                3 => [
                    'data' => ['visibility' => 'visible'],
                    'entity' => $customer3,
                ],
            ]
        );
        $customerGroupForm = $this->createMock(FormInterface::class);
        $customerGroupForm->method('getData')->willReturn([]);

        $form->method('has')->willReturnMap(
            [
                ['all', true],
                ['customer', true],
                ['customerGroup', true],
            ]
        );
        $form->method('get')->willReturnMap(
            [
                ['all', $allForm],
                ['customer', $customerForm],
                ['customerGroup', $customerGroupForm],
            ]
        );

        $productVisibility = new ProductVisibility();
        $customerProductVisibility1 = (new CustomerProductVisibility())->setVisibility('hidden');
        $customerProductVisibility2 = (new CustomerProductVisibility())->setVisibility('visible');
        $customerProductVisibility3 = new CustomerProductVisibility();
        $this->fieldDataProvider->method('findFormFieldData')
            ->willReturnMap(
                [
                    [$form, 'all', null],
                    [$form, 'customer', [1 => $customerProductVisibility1, 2 => $customerProductVisibility2]],
                    [$form, 'customerGroup', []],
                ]
            );
        // expect new visibility entities will be created with following arguments
        $this->fieldDataProvider->method('createFormFieldData')
            ->willReturnMap(
                [
                    [$form, 'all', null, $productVisibility],
                    [$form, 'customer', $customer3, $customerProductVisibility3],
                ]
            );

        // assert that new visibility entity persisted when visibility value is not default
        $this->em->expects($this->at(0))
            ->method('persist')
            ->with($productVisibility);

        // assert that existing customer visibility with new non default will be persisted
        $this->em->expects($this->at(1))
            ->method('persist')
            ->with($customerProductVisibility1);

        // assert that existing customer visibility with new default will be remove
        $this->em->expects($this->at(2))
            ->method('remove')
            ->with($customerProductVisibility2);

        // assert that customer visibility with non default will be persisted
        $this->em->expects($this->at(3))
            ->method('persist')
            ->with($customerProductVisibility3);
        $this->dataHandler->saveForm($form, $targetEntity);

        $this->assertEquals($productVisibility->getVisibility(), 'hidden');
        $this->assertEquals($customerProductVisibility1->getVisibility(), 'category');
        $this->assertEquals($customerProductVisibility3->getVisibility(), 'visible');
    }
}
