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

    /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    /** @var VisibilityFormFieldDataProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $fieldDataProvider;

    /** @var VisibilityFormPostSubmitDataHandler */
    private $dataHandler;

    protected function setUp(): void
    {
        $this->fieldDataProvider = $this->createMock(VisibilityFormFieldDataProvider::class);
        $this->em = $this->createMock(EntityManagerInterface::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($this->em);

        $this->dataHandler = new VisibilityFormPostSubmitDataHandler(
            $doctrine,
            $this->fieldDataProvider
        );
    }

    public function testSaveInvalidForm()
    {
        $form = $this->createMock(FormInterface::class);
        /** @var Product $targetEntity */
        $targetEntity = $this->getEntity(Product::class, ['id' => 1]);
        $form->expects($this->any())
            ->method('getData')
            ->willReturn($targetEntity);
        $form->expects($this->any())
            ->method('isSubmitted')
            ->willReturn(true);
        $form->expects($this->any())
            ->method('isValid')
            ->willReturn(false);

        $this->em->expects($this->never())
            ->method('persist');
        $form->expects($this->never())
            ->method('get');

        $this->dataHandler->saveForm($form, $targetEntity);
    }

    public function testSaveForm()
    {
        $form = $this->createMock(FormInterface::class);
        /** @var Product $targetEntity */
        $targetEntity = $this->getEntity(Product::class, ['id' => 1]);
        $form->expects($this->any())
            ->method('getData')
            ->willReturn($targetEntity);
        $form->expects($this->any())
            ->method('isSubmitted')
            ->willReturn(true);
        $form->expects($this->any())
            ->method('isValid')
            ->willReturn(true);

        $allForm = $this->createMock(FormInterface::class);
        $allForm->expects($this->any())
            ->method('getData')
            ->willReturn('hidden');
        $customerForm = $this->createMock(FormInterface::class);
        $customer3 = $this->getEntity(Customer::class, ['id' => 3]);
        $customerForm->expects($this->any())
            ->method('getData')
            ->willReturn([
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
            ]);
        $customerGroupForm = $this->createMock(FormInterface::class);
        $customerGroupForm->expects($this->any())
            ->method('getData')
            ->willReturn([]);

        $form->expects($this->any())
            ->method('has')
            ->willReturnMap([
                ['all', true],
                ['customer', true],
                ['customerGroup', true],
            ]);
        $form->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['all', $allForm],
                ['customer', $customerForm],
                ['customerGroup', $customerGroupForm],
            ]);

        $productVisibility = new ProductVisibility();
        $customerProductVisibility1 = (new CustomerProductVisibility())->setVisibility('hidden');
        $customerProductVisibility2 = (new CustomerProductVisibility())->setVisibility('visible');
        $customerProductVisibility3 = new CustomerProductVisibility();
        $this->fieldDataProvider->expects($this->any())
            ->method('findFormFieldData')
            ->willReturnMap([
                [$form, 'all', null],
                [$form, 'customer', [1 => $customerProductVisibility1, 2 => $customerProductVisibility2]],
                [$form, 'customerGroup', []],
            ]);
        // expect new visibility entities will be created with following arguments
        $this->fieldDataProvider->expects($this->any())
            ->method('createFormFieldData')
            ->willReturnMap([
                [$form, 'all', null, $productVisibility],
                [$form, 'customer', $customer3, $customerProductVisibility3],
            ]);

        // assert that new visibility entity persisted when visibility value is not default
        // assert that existing customer visibility with new non default will be persisted
        // assert that customer visibility with non default will be persisted
        $this->em->expects($this->exactly(3))
            ->method('persist')
            ->withConsecutive(
                [$productVisibility],
                [$customerProductVisibility1],
                [$customerProductVisibility3]
            );
        // assert that existing customer visibility with new default will be remove
        $this->em->expects($this->once())
            ->method('remove')
            ->with($customerProductVisibility2);

        $this->dataHandler->saveForm($form, $targetEntity);

        $this->assertEquals('hidden', $productVisibility->getVisibility());
        $this->assertEquals('category', $customerProductVisibility1->getVisibility());
        $this->assertEquals('visible', $customerProductVisibility3->getVisibility());
    }
}
