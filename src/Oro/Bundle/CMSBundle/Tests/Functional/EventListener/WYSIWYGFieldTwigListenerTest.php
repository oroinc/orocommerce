<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\EventListener;

use Oro\Bundle\CMSBundle\WYSIWYG\WYSIWYGProcessedDTO;
use Oro\Bundle\CMSBundle\WYSIWYG\WYSIWYGTwigFunctionProcessorInterface;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Twig\Environment;
use Twig\Sandbox\SecurityPolicy;
use Twig\TwigFunction;

class WYSIWYGFieldTwigListenerTest extends WebTestCase
{
    /** @var \Doctrine\Common\Persistence\ObjectManager */
    private $em;

    /** @var Localization */
    private $localization;

    protected function setUp()
    {
        $this->initClient();

        $this->em = $this->getContainer()->get('doctrine')->getManager();
        $this->localization = $this->em->getRepository(Localization::class)
            ->findOneBy(['formattingCode' => 'en_US']);
    }

    public function testProcessRelation(): void
    {
        $expectedCalls = [
            // On persist
            [
                'wysiwyg' => [
                    'test' => [
                        ['default', 'wysiwyg'],
                        ['en', 'wysiwyg'],
                    ],
                ],
            ],

            [
                'wysiwyg_style' => [
                    'test' => [
                        ['default', 'wysiwyg_styles'],
                        ['en', 'wysiwyg_styles'],
                    ],
                ],
            ],

            // On update
            [
                'wysiwyg' => [
                    'test' => [
                        ['default', 'wysiwyg'],
                        ['default', 'wysiwyg2'],
                    ],
                ],
            ],

            [
                'wysiwyg_style' => [
                    'test' => [
                        ['en', 'wysiwyg_styles'],
                        ['en', 'wysiwyg_styles2'],
                    ],
                ],
            ],
        ];

        $this->assertProcessTwigFunctions($expectedCalls);

        $product = new Product();
        $this->persistProduct($product);
        $this->updateProduct($product);

        $this->assertCount(0, $expectedCalls);
    }

    /**
     * @param array $expectedCalls
     */
    private function assertProcessTwigFunctions(array &$expectedCalls): void
    {
        /** @var SecurityPolicy $securityPolicy */
        $securityPolicy = $this->getContainer()->get('oro_cms.twig.content_security_policy');
        $securityPolicy->setAllowedFunctions(['test']);

        /** @var Environment $twig */
        $twig = $this->getContainer()->get('oro_cms.twig.renderer');
        $twig->addFunction(new TwigFunction('test'));

        $processor = $this->createMock(WYSIWYGTwigFunctionProcessorInterface::class);
        $this->getContainer()->set('oro_cms.wysiwyg.chain_twig_function_processor', $processor);

        $processor->expects($this->any())
            ->method('getApplicableMapping')
            ->willReturn([
                WYSIWYGTwigFunctionProcessorInterface::FIELD_CONTENT_TYPE => ['test'],
                WYSIWYGTwigFunctionProcessorInterface::FIELD_STYLES_TYPE => ['test'],
            ]);

        $processor->expects($this->atLeast(4))
            ->method('processTwigFunctions')
            ->willReturnCallback(
                function (WYSIWYGProcessedDTO $processedDTO, array $twigFunctionCalls) use (&$expectedCalls) {
                    if (empty($twigFunctionCalls['wysiwyg']) && empty($twigFunctionCalls['wysiwyg_style'])) {
                        return false;
                    }

                    $this->assertEquals(reset($expectedCalls), $twigFunctionCalls);
                    unset($expectedCalls[key($expectedCalls)]);

                    return true;
                }
            );
    }

    private function persistProduct(Product $product): void
    {
        $product->setDefaultName('testTitle');
        $product->setSku('test-1');
        $product->addDescription(
            (new LocalizedFallbackValue())
                ->setWysiwyg(
                    "<div>{{ test('default', 'wysiwyg') }}</div>"
                )
                ->setWysiwygStyle(
                    ".test {
                        backgroud-image: url({{ test('default', 'wysiwyg_styles') }})
                    }"
                )
        );
        $product->addDescription(
            (new LocalizedFallbackValue())
                ->setLocalization($this->localization)
                ->setWysiwyg(
                    "<div>{{ test('en', 'wysiwyg') }}</div>"
                )
                ->setWysiwygStyle(
                    ".test {
                        background-image: url({{ test('en', 'wysiwyg_styles') }})
                    }"
                )
        );

        $this->em->persist($product);
        $this->em->flush();

        $this->getContainer()->get('oro_cms.event_listener.wysiwyg_field_twig_listener.test')->onTerminate();
    }

    private function updateProduct(Product $product): void
    {
        // Force modify owner entity to process his associations
        $product->setUpdatedAt(new \DateTime());

        $product->getDefaultDescription()
            ->setWysiwyg(
                "<div>{{ test('default', 'wysiwyg') }}{{ test('default', 'wysiwyg2') }}</div>"
            )
            ->setWysiwygStyle(null);

        $product->getDescription($this->localization)
            ->setWysiwyg(null)
            ->setWysiwygStyle(
                ".test {
                    backgroud-image: url({{ test('en', 'wysiwyg_styles') }})
                }
                .test2 {
                    backgroud-image: url({{ test('en', 'wysiwyg_styles2') }})
                }"
            );

        $this->em->flush();

        $this->getContainer()->get('oro_cms.event_listener.wysiwyg_field_twig_listener.test')->onTerminate();
    }
}
