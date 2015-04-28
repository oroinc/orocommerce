<?php

namespace OroB2B\Bundle\RFPAdminBundle\Tests\Unit\Form\Handler;

use Oro\Component\Testing\Unit\FormHandlerTestCase;

use OroB2B\Bundle\RFPAdminBundle\Entity\RequestStatus;
use OroB2B\Bundle\RFPAdminBundle\Form\Handler\RequestStatusHandler;

class RequestStatusHandlerTest extends FormHandlerTestCase
{
    const DEFAULT_LOCALE = 'ru';

    /**
     * @var RequestStatusHandler
     */
    protected $handler;

    /**
     * @var RequestStatus
     */
    protected $entity;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->entity = new RequestStatus();
        $this->handler = new RequestStatusHandler($this->form, $this->request, $this->manager);
        $this->handler->setDefaultLocale(self::DEFAULT_LOCALE);
    }

    /**
     * @dataProvider supportedMethods
     * @param string $method
     * @param boolean $isValid
     * @param boolean $isProcessed
     */
    public function testProcessSupportedRequest($method, $isValid, $isProcessed)
    {
        $reflection = new \ReflectionProperty(get_class($this->entity), 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($this->entity, 1);

        $this->manager->expects($this->once())
            ->method('refresh')
            ->with($this->entity);

        parent::testProcessSupportedRequest($method, $isValid, $isProcessed);

        $this->assertEquals(self::DEFAULT_LOCALE, $this->entity->getLocale());
    }
}
