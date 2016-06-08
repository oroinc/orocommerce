<?php

namespace OroB2B\Bundle\SEOBundle\Tests\Unit\EventListener;

use Oro\Component\Testing\Unit\FormViewListenerTestCase;
use OroB2B\Bundle\CMSBundle\Entity\Page;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\SEOBundle\EventListener\FormViewListener;
use Symfony\Component\Form\FormView;

class BaseFormViewListenerTestCase extends FormViewListenerTestCase
{
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

    protected function getEnvironmentForView($entityObject)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $env */
        $env = $this->getMockBuilder('\Twig_Environment')
            ->disableOriginalConstructor()
            ->getMock();

        $env->expects($this->once())
            ->method('render')
            ->with('OroB2BSEOBundle:SEO:view.html.twig', ['entity' => $entityObject, 'labelPrefix' => $this->listener->getMetaFieldLabelPrefix()])
            ->willReturn('');

        return $env;
    }

    protected function getEnvironmentForEdit()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $env */
        $env = $this->getMockBuilder('\Twig_Environment')
            ->disableOriginalConstructor()
            ->getMock();

        $env->expects($this->once())
            ->method('render')
            ->with('OroB2BSEOBundle:SEO:update.html.twig', ['form' => new FormView()])
            ->willReturn('');

        return $env;
    }

    protected function getEventForView($env)
    {
        $event = $this->getBeforeListRenderEvent();

        $event->expects($this->once())
            ->method('getEnvironment')
            ->willReturn($env);

        return $event;
    }

    protected function getEventForEdit($env)
    {
        $event = $this->getEventForView($env);

        $event->expects($this->once())
            ->method('getFormView')
            ->willReturn(new FormView());

        return $event;
    }
}
