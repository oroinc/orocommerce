<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\EventListener;

use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use OroB2B\Bundle\AccountBundle\Form\Type\EntityVisibilityType;
use OroB2B\Bundle\AccountBundle\EventListener\ProductVisibilityPostSubmitListener;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class ProductVisibilityPostSubmitListenerTest extends VisibilityAbstractListenerTestCase
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
        /** @var Product $category */
        $product = $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\Product', self::PRODUCT_ID);
        
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $visibilityForm = $this->getMock('Symfony\Component\Form\FormInterface');

        /** @var FormConfigInterface|\PHPUnit_Framework_MockObject_MockObject $formConfigInterface */
        $formConfigInterface = $this->getMock('Symfony\Component\Form\FormConfigInterface');

        $form = new Form($formConfigInterface);
        $form->add($visibilityForm);

        /** @var AfterFormProcessEvent |\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder('Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent')
            ->disableOriginalConstructor()
            ->getMock();
        
        $event->expects($this->any())
            ->method('getForm')
            ->willReturn($form);

        return $event;
    }

    /**
     * @param string $className
     * @param int $id
     *
     * @return object
     */
    protected function getEntity($className, $id)
    {
        $entity = new $className;

        $reflectionClass = new \ReflectionClass($className);
        $method = $reflectionClass->getProperty('id');
        $method->setAccessible(true);
        $method->setValue($entity, $id);

        return $entity;
    }
}
