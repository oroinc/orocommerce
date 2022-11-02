<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\EventListener;

use Symfony\Component\Form\FormView;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

abstract class BaseFormViewListenerTestCase extends \PHPUnit\Framework\TestCase
{
    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $translator;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(function ($id) {
                return $id . '.trans';
            });
    }

    protected function getEnvironmentForView(object $entityObject, string $labelPrefix): Environment
    {
        $env = $this->createMock(Environment::class);
        $env->expects($this->exactly(3))
            ->method('render')
            ->withConsecutive(
                [
                    '@OroSEO/SEO/title_view.html.twig',
                    [
                        'entity' => $entityObject,
                        'labelPrefix' => $labelPrefix
                    ]
                ],
                [
                    '@OroSEO/SEO/description_view.html.twig',
                    [
                        'entity' => $entityObject,
                        'labelPrefix' => $labelPrefix
                    ]
                ],
                [
                    '@OroSEO/SEO/keywords_view.html.twig',
                    [
                        'entity' => $entityObject,
                        'labelPrefix' => $labelPrefix
                    ]
                ]
            )
            ->willReturnMap([
                [
                    '@OroSEO/SEO/title_view.html.twig',
                    [
                        'entity' => $entityObject,
                        'labelPrefix' => $labelPrefix
                    ],
                    ''
                ],
                [
                    '@OroSEO/SEO/description_view.html.twig',
                    [
                        'entity' => $entityObject,
                        'labelPrefix' => $labelPrefix
                    ],
                    ''
                ],
                [
                    '@OroSEO/SEO/keywords_view.html.twig', [
                        'entity' => $entityObject,
                        'labelPrefix' => $labelPrefix
                    ],
                    ''
                ]
            ]);

        return $env;
    }

    protected function getEnvironmentForEdit(): Environment
    {
        $env = $this->createMock(Environment::class);
        $env->expects($this->exactly(3))
            ->method('render')
            ->withConsecutive(
                ['@OroSEO/SEO/title_update.html.twig', ['form' => new FormView()]],
                ['@OroSEO/SEO/description_update.html.twig', ['form' => new FormView()]],
                ['@OroSEO/SEO/keywords_update.html.twig', ['form' => new FormView()]],
            )
            ->will($this->onConsecutiveCalls('', '', ''));

        return $env;
    }
}
