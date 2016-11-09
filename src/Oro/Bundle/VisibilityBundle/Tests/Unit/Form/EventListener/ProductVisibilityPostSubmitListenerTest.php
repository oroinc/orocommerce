<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Form\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\VisibilityBundle\Form\EventListener\ProductVisibilityPostSubmitListener;
use Oro\Bundle\VisibilityBundle\Form\EventListener\VisibilityFormPostSubmitDataHandler;
use Symfony\Component\Form\FormInterface;

class ProductVisibilityPostSubmitListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProductVisibilityPostSubmitListener
     */
    protected $listener;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var VisibilityFormPostSubmitDataHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $dataHandler;

    protected function setUp()
    {
        $this->registry = $this->getMock(ManagerRegistry::class);

        $this->dataHandler = $this->getMockBuilder(VisibilityFormPostSubmitDataHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new ProductVisibilityPostSubmitListener($this->dataHandler, $this->registry);
    }

    public function testOnPostSubmit()
    {
        $form = $this->getMock(FormInterface::class);
        $product = new Product();
        $form->method('getData')->willReturn($product);

        $allForm = $this->getMock(FormInterface::class);
        $accountForm = $this->getMock(FormInterface::class);
        $accountGroupForm = $this->getMock(FormInterface::class);

        $form->method('all')->willReturn([
            $allForm,
            $accountForm,
            $accountGroupForm
        ]);

        $em = $this->getMock(EntityManagerInterface::class);
        $this->registry->method('getManagerForClass')->willReturn($em);

        // assert that all forms where saved trough data handler
        $this->dataHandler->expects($this->exactly(3))
            ->method('saveForm')
            ->withConsecutive(
                [$allForm, $product],
                [$accountForm, $product],
                [$accountGroupForm, $product]
            );

        $event = new AfterFormProcessEvent($form, $product);
        $this->listener->onPostSubmit($event);
    }
}
