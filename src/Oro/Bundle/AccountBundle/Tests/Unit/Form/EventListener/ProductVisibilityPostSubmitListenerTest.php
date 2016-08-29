<?php

namespace Oro\Bundle\AccountBundle\Tests\Unit\Form\EventListener;

use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\AccountBundle\Form\Type\EntityVisibilityType;
use Oro\Bundle\AccountBundle\Form\EventListener\ProductVisibilityPostSubmitListener;

use Symfony\Component\Form\FormInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class ProductPostSubmitVisibilityListenerTest extends AbstractVisibilityListenerTestCase
{
    const PRODUCT_ID = 42;

    /** @var ProductVisibilityPostSubmitListener */
    protected $listener;

    /**
     * @return ProductVisibilityPostSubmitListener
     */
    public function getListener()
    {
        $listener = new ProductVisibilityPostSubmitListener($this->registry);
        $listener->setVisibilityField(EntityVisibilityType::VISIBILITY);

        return $listener;
    }

    public function testOnPostSubmit()
    {
        $event = $this->getFormAwareEventMock();

        $this->listener->onPostSubmit($event);
    }

    /**
     * @return AfterFormProcessEvent|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getFormAwareEventMock()
    {
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $visibilityForm = $this->getMock('Symfony\Component\Form\FormInterface');

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');

        $form->expects($this->any())
            ->method('all')
            ->willReturn([$visibilityForm]);

        /** @var AfterFormProcessEvent |\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder('Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent')
            ->disableOriginalConstructor()
            ->getMock();
        
        $event->expects($this->any())
            ->method('getForm')
            ->willReturn($form);

        return $event;
    }
}
