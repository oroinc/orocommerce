<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\EventListener;

use Oro\Bundle\SEOBundle\EventListener\BaseFormViewListener;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Component\Testing\Unit\FormViewListenerTestCase;
use Oro\Bundle\UIBundle\View\ScrollData;
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

        $this->requestStack = $this->createMock('Symfony\Component\HttpFoundation\RequestStack');
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

        $env->expects($this->exactly(2))
            ->method('render')
            ->willReturnMap([
                [
                    'OroSEOBundle:SEO:description_view.html.twig',
                    [
                        'entity' => $entityObject,
                        'labelPrefix' => $this->listener->getMetaFieldLabelPrefix()
                    ],
                    ''
                ],
                [
                    'OroSEOBundle:SEO:keywords_view.html.twig', [
                        'entity' => $entityObject,
                        'labelPrefix' => $this->listener->getMetaFieldLabelPrefix()
                    ],
                    ''
                ]
            ]);

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

        $env->expects($this->exactly(2))
            ->method('render')
            ->willReturnMap([
                ['OroSEOBundle:SEO:description_update.html.twig', ['form' => new FormView()], ''],
                ['OroSEOBundle:SEO:keywords_update.html.twig', ['form' => new FormView()], ''],
            ]);

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

    /**
     * {@inheritdoc}
     */
    protected function getScrollData()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ScrollData $scrollData */
        $scrollData = $this->getMock('Oro\Bundle\UIBundle\View\ScrollData');

        $scrollData->expects($this->once())
            ->method('addNamedBlock');

        $scrollData->expects($this->any())
            ->method('addSubBlock');

        $scrollData->expects($this->any())
            ->method('addSubBlockData');

        return $scrollData;
    }
}
