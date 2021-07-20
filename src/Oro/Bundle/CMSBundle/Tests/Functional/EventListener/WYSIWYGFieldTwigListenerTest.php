<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\EventListener;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CMSBundle\WYSIWYG\WYSIWYGProcessedDTO;
use Oro\Bundle\CMSBundle\WYSIWYG\WYSIWYGTwigFunctionProcessorInterface;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductDescription;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Twig\Environment;
use Twig\Sandbox\SecurityPolicy;
use Twig\TwigFunction;

class WYSIWYGFieldTwigListenerTest extends WebTestCase
{
    /** @var ObjectManager */
    private $em;

    /** @var Localization */
    private $localization;

    protected function setUp(): void
    {
        $this->initClient();

        $this->getOptionalListenerManager()->enableListener('oro_cms.event_listener.wysiwyg_field_twig_listener');

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

        $processor->expects($this->exactly(7))
            ->method('processTwigFunctions')
            ->willReturnCallback(
                function (WYSIWYGProcessedDTO $processedDTO, array $twigFunctionCalls) use (&$expectedCalls) {
                    if (empty($twigFunctionCalls['wysiwyg']) && empty($twigFunctionCalls['wysiwyg_style'])) {
                        $this->assertNotEquals('descriptions', $processedDTO->requireOwnerEntityFieldName());
                        return false;
                    }

                    ksort($twigFunctionCalls);

                    $this->assertContains($twigFunctionCalls, $expectedCalls);
                    unset($expectedCalls[array_search($twigFunctionCalls, $expectedCalls, true)]);

                    return true;
                }
            );
    }

    private function persistProduct(Product $product): void
    {
        $product->setDefaultName('testTitle');
        $product->setSku('test-1');
        $product->addDescription(
            (new ProductDescription())
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
            (new ProductDescription())
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

        $this->getContainer()->get('oro_cms.tests.event_listener.wysiwyg_field_twig_listener')->onTerminate();
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

        // Ensures that scheduled operations are executed before the clear - checks that preClear() method works.
        $this->em->clear();
    }
}
