<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Form\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\VisibilityBundle\Form\EventListener\CategoryVisibilityPostSubmitListener;
use Oro\Bundle\VisibilityBundle\Form\EventListener\VisibilityFormPostSubmitDataHandler;
use Symfony\Component\Form\Test\FormInterface;

class CategoryVisibilityPostSubmitListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var VisibilityFormPostSubmitDataHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $dataHandler;

    /** @var CategoryVisibilityPostSubmitListener */
    private $listener;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->dataHandler = $this->createMock(VisibilityFormPostSubmitDataHandler::class);

        $this->listener = new CategoryVisibilityPostSubmitListener($this->dataHandler, $this->registry);
    }

    public function testOnPostSubmit()
    {
        $form = $this->createMock(FormInterface::class);
        $category = new Category();
        $visibilityForm = $this->createMock(FormInterface::class);
        $visibilityForm->expects($this->any())
            ->method('getData')
            ->willReturn($category);
        $form->expects($this->any())
            ->method('get')
            ->with('visibility')
            ->willReturn($visibilityForm);
        $event = new AfterFormProcessEvent($form, $category);

        $em = $this->createMock(EntityManagerInterface::class);
        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);

        $this->dataHandler->expects($this->once())
            ->method('saveForm')
            ->with($visibilityForm, $category);
        $this->listener->onPostSubmit($event);
    }
}
