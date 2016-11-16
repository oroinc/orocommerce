<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Form\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\VisibilityBundle\Form\EventListener\CategoryVisibilityPostSubmitListener;
use Oro\Bundle\VisibilityBundle\Form\EventListener\VisibilityFormPostSubmitDataHandler;
use Symfony\Component\Form\Test\FormInterface;

class CategoryVisibilityPostSubmitListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CategoryVisibilityPostSubmitListener
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

        $this->listener = new CategoryVisibilityPostSubmitListener($this->dataHandler, $this->registry);
    }

    public function testOnPostSubmit()
    {
        $form = $this->getMock(FormInterface::class);
        $category = new Category();
        $visibilityForm = $this->getMock(FormInterface::class);
        $visibilityForm->method('getData')->willReturn($category);
        $form->method('get')->with('visibility')->willReturn($visibilityForm);
        $event = new AfterFormProcessEvent($form, $category);

        $em = $this->getMock(EntityManagerInterface::class);
        $this->registry->method('getManagerForClass')->willReturn($em);

        $this->dataHandler->expects($this->once())
            ->method('saveForm')
            ->with($visibilityForm, $category);
        $this->listener->onPostSubmit($event);
    }
}
