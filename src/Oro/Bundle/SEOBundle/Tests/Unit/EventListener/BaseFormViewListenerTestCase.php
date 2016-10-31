<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\EventListener;

use Oro\Bundle\SEOBundle\EventListener\BaseFormViewListener;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Component\Testing\Unit\FormViewListenerTestCase;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class BaseFormViewListenerTestCase extends FormViewListenerTestCase
{
    /**
     * @var BaseFormViewListener $listener
     */
    protected $listener;

    /** @var  Request|\PHPUnit_Framework_MockObject_MockObject */
    protected $request;

    /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject $requestStack */
    protected $requestStack;

    protected function tearDown()
    {
        unset($this->doctrineHelper, $this->translator);
    }

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->request = $this->getRequest();

        $this->requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');
        $this->requestStack->expects($this->any())->method('getCurrentRequest')->willReturn($this->request);
    }

    /**
     * @param object $entityObject
     * @return \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment
     */
    protected function getEnvironmentForView($entityObject)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $env */
        $env = $this->getMockBuilder('\Twig_Environment')
            ->disableOriginalConstructor()
            ->getMock();

        $env->expects($this->once())
            ->method('render')
            ->with('OroSEOBundle:SEO:view.html.twig', [
                'entity' => $entityObject,
                'labelPrefix' => $this->listener->getMetaFieldLabelPrefix()
            ])
            ->willReturn('');

        return $env;
    }

    /**
     * @return \Twig_Environment
     */
    protected function getEnvironmentForEdit()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $env */
        $env = $this->getMockBuilder('\Twig_Environment')
            ->disableOriginalConstructor()
            ->getMock();

        $env->expects($this->once())
            ->method('render')
            ->with('OroSEOBundle:SEO:update.html.twig', ['form' => new FormView()])
            ->willReturn('');

        return $env;
    }

    /**
     * @param \Twig_Environment $env
     * @return BeforeListRenderEvent|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getEventForView(\Twig_Environment $env)
    {
        $event = $this->getBeforeListRenderEvent();

        $event->expects($this->once())
            ->method('getEnvironment')
            ->willReturn($env);

        return $event;
    }

    /**
     * @param \Twig_Environment $env
     * @return BeforeListRenderEvent
     */
    protected function getEventForEdit(\Twig_Environment $env)
    {
        $event = $this->getEventForView($env);

        $event->expects($this->once())
            ->method('getFormView')
            ->willReturn(new FormView());

        return $event;
    }
}
