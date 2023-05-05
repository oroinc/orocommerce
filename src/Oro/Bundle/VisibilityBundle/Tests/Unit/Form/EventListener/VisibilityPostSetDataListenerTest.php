<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Form\EventListener;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ScopeBundle\Tests\Unit\Stub\StubScope;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\VisibilityBundle\Form\EventListener\VisibilityFormFieldDataProvider;
use Oro\Bundle\VisibilityBundle\Form\EventListener\VisibilityPostSetDataListener;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\Test\FormInterface;

class VisibilityPostSetDataListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var VisibilityFormFieldDataProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $fieldDataProvider;

    /** @var VisibilityPostSetDataListener */
    private $listener;

    protected function setUp(): void
    {
        $this->fieldDataProvider = $this->createMock(VisibilityFormFieldDataProvider::class);

        $this->listener = new VisibilityPostSetDataListener(
            $this->fieldDataProvider
        );
    }

    public function testOnPostSetData()
    {
        $product = $this->getEntity(Product::class, ['id' => 1]);
        $form = $this->createMock(FormInterface::class);
        $formConfig = $this->createMock(FormConfigInterface::class);
        $formConfig->expects($this->any())
            ->method('getOption')
            ->with('allClass')
            ->willReturn(ProductVisibility::class);
        $form->expects($this->any())
            ->method('getData')
            ->willReturn($product);
        $form->expects($this->any())
            ->method('getConfig')
            ->willReturn($formConfig);

        $customer1 = $this->getEntity(Customer::class, ['id' => 2]);
        $customer2 = $this->getEntity(Customer::class, ['id' => 4]);
        $customerGroup1 = $this->getEntity(CustomerGroup::class, ['id' => 3]);
        $customerGroup2 = $this->getEntity(CustomerGroup::class, ['id' => 5]);
        $this->fieldDataProvider->expects($this->exactly(3))
            ->method('findFormFieldData')
            ->willReturnMap([
                [$form, 'all', null],
                [
                    $form,
                    'customerGroup',
                    [
                        3 => (new CustomerGroupProductVisibility())->setVisibility('visible')
                            ->setScope(new StubScope(['customerGroup' => $customerGroup1, 'customer' => null])),
                        5 => (new CustomerGroupProductVisibility())->setVisibility('hidden')
                            ->setScope(new StubScope(['customerGroup' => $customerGroup2, 'customer' => null])),
                    ],
                ],
                [
                    $form,
                    'customer',
                    [
                        2 => (new CustomerProductVisibility())->setVisibility('visible')
                            ->setScope(new StubScope(['customerGroup' => null, 'customer' => $customer1])),
                        4 => (new CustomerGroupProductVisibility())->setVisibility('hidden')
                            ->setScope(new StubScope(['customerGroup' => null, 'customer' => $customer2])),
                    ],
                ]
            ]);

        $allForm = $this->createMock(FormInterface::class);
        $customerForm = $this->createMock(FormInterface::class);
        $customerGroupForm = $this->createMock(FormInterface::class);

        $form->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['all', $allForm],
                ['customer', $customerForm],
                ['customerGroup', $customerGroupForm],
            ]);

        // assert data was set
        $allForm->expects($this->once())
            ->method('setData')
            ->with('category');

        $customerGroupForm->expects($this->once())
            ->method('setData')
            ->with(
                [
                    3 => ['entity' => $customerGroup1, 'data' => ['visibility' => 'visible']],
                    5 => ['entity' => $customerGroup2, 'data' => ['visibility' => 'hidden']],
                ]
            );
        $customerForm->expects($this->once())
            ->method('setData')
            ->with(
                [
                    2 => ['entity' => $customer1, 'data' => ['visibility' => 'visible']],
                    4 => ['entity' => $customer2, 'data' => ['visibility' => 'hidden']],
                ]
            );

        $event = new FormEvent($form, []);
        $this->listener->onPostSetData($event);
    }
}
