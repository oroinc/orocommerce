<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Form\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\VisibilityBundle\Form\EventListener\CategoryVisibilityPostSubmitListener;
use Oro\Bundle\VisibilityBundle\Form\EventListener\VisibilityFormPostSubmitDataHandler;
use Oro\Bundle\VisibilityBundle\Form\Type\EntityVisibilityType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Test\FormInterface;

class CategoryVisibilityPostSubmitListenerTest extends TestCase
{
    private ManagerRegistry|MockObject $registry;

    private VisibilityFormPostSubmitDataHandler|MockObject $dataHandler;

    private CategoryVisibilityPostSubmitListener $listener;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->dataHandler = $this->createMock(VisibilityFormPostSubmitDataHandler::class);

        $this->listener = new CategoryVisibilityPostSubmitListener($this->dataHandler, $this->registry);
    }

    public function testOnPostSubmitNoVisibilityForm(): void
    {
        $category = new Category();

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('has')
            ->with(EntityVisibilityType::VISIBILITY)
            ->willReturn(false);

        $event = new AfterFormProcessEvent($form, $category);

        $this->registry->expects(self::never())
            ->method(self::anything())
            ->withAnyParameters();

        $this->dataHandler->expects(self::never())
            ->method(self::anything())
            ->withAnyParameters();

        $this->listener->onPostSubmit($event);
    }

    public function testOnPostSubmit(): void
    {
        $form = $this->createMock(FormInterface::class);
        $category = new Category();
        $visibilityForm = $this->createMock(FormInterface::class);
        $visibilityForm->expects(self::any())
            ->method('getData')
            ->willReturn($category);
        $form->expects(self::once())
            ->method('has')
            ->with(EntityVisibilityType::VISIBILITY)
            ->willReturn(true);
        $form->expects(self::any())
            ->method('get')
            ->with('visibility')
            ->willReturn($visibilityForm);
        $event = new AfterFormProcessEvent($form, $category);

        $em = $this->createMock(EntityManagerInterface::class);
        $this->registry->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($em);

        $this->dataHandler->expects(self::once())
            ->method('saveForm')
            ->with($visibilityForm, $category);
        $this->listener->onPostSubmit($event);
    }
}
