<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Form\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\VisibilityBundle\Form\EventListener\ProductVisibilityPostSubmitListener;
use Oro\Bundle\VisibilityBundle\Form\EventListener\VisibilityFormPostSubmitDataHandler;
use Symfony\Component\Form\FormInterface;

class ProductVisibilityPostSubmitListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var VisibilityFormPostSubmitDataHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $dataHandler;

    /** @var ProductVisibilityPostSubmitListener */
    private $listener;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->dataHandler = $this->createMock(VisibilityFormPostSubmitDataHandler::class);

        $this->listener = new ProductVisibilityPostSubmitListener($this->dataHandler, $this->registry);
    }

    public function testOnPostSubmit()
    {
        $form = $this->createMock(FormInterface::class);
        $product = new Product();
        $form->expects($this->any())
            ->method('getData')
            ->willReturn($product);

        $allForm = $this->createMock(FormInterface::class);
        $customerForm = $this->createMock(FormInterface::class);
        $customerGroupForm = $this->createMock(FormInterface::class);

        $form->expects($this->any())
            ->method('all')
            ->willReturn([$allForm, $customerForm, $customerGroupForm]);

        $em = $this->createMock(EntityManagerInterface::class);
        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);

        // assert that all forms where saved trough data handler
        $this->dataHandler->expects($this->exactly(3))
            ->method('saveForm')
            ->withConsecutive(
                [$allForm, $product],
                [$customerForm, $product],
                [$customerGroupForm, $product]
            );

        $event = new AfterFormProcessEvent($form, $product);
        $this->listener->onPostSubmit($event);
    }
}
