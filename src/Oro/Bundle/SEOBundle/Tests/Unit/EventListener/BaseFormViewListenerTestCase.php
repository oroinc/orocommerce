<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\EventListener;

use Oro\Component\Testing\Unit\FormViewListenerTestCase;
use Symfony\Component\Form\FormView;

abstract class BaseFormViewListenerTestCase extends FormViewListenerTestCase
{
    /**
     * @param object $entityObject
     * @param string $labelPrefix
     * @return \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment
     */
    protected function getEnvironmentForView($entityObject, $labelPrefix)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $env */
        $env = $this->getMockBuilder(\Twig_Environment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $env->expects($this->exactly(3))
            ->method('render')
            ->willReturnMap([
                [
                    'OroSEOBundle:SEO:title_view.html.twig',
                    [
                        'entity' => $entityObject,
                        'labelPrefix' => $labelPrefix
                    ],
                    ''
                ],
                [
                    'OroSEOBundle:SEO:description_view.html.twig',
                    [
                        'entity' => $entityObject,
                        'labelPrefix' => $labelPrefix
                    ],
                    ''
                ],
                [
                    'OroSEOBundle:SEO:keywords_view.html.twig', [
                        'entity' => $entityObject,
                        'labelPrefix' => $labelPrefix
                    ],
                    ''
                ]
            ]);

        return $env;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment
     */
    protected function getEnvironmentForEdit()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $env */
        $env = $this->getMockBuilder(\Twig_Environment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $env->expects($this->exactly(3))
            ->method('render')
            ->willReturnMap([
                ['OroSEOBundle:SEO:title_update.html.twig', ['form' => new FormView()], ''],
                ['OroSEOBundle:SEO:description_update.html.twig', ['form' => new FormView()], ''],
                ['OroSEOBundle:SEO:keywords_update.html.twig', ['form' => new FormView()], ''],
            ]);

        return $env;
    }
}
