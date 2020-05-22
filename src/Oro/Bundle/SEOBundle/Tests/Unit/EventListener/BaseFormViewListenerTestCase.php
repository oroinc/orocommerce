<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\EventListener;

use Symfony\Component\Form\FormView;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

abstract class BaseFormViewListenerTestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $translator;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(
                function ($id) {
                    return $id . '.trans';
                }
            );
    }

    /**
     * @param object $entityObject
     * @param string $labelPrefix
     * @return \PHPUnit\Framework\MockObject\MockObject|Environment
     */
    protected function getEnvironmentForView($entityObject, $labelPrefix)
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|Environment $env */
        $env = $this->createMock(Environment::class);

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
     * @return \PHPUnit\Framework\MockObject\MockObject|Environment
     */
    protected function getEnvironmentForEdit()
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|Environment $env */
        $env = $this->createMock(Environment::class);

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
