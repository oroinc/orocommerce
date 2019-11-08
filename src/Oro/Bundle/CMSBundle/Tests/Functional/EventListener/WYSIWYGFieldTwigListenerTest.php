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
            ->findOneBy(['formattingCode' => 'en']);
    }

    public function testPerformAssociations(): void
    {
        $expectedCalls = [
            // Persist
            ['default', 'wysiwyg'],
            ['default', 'wysiwyg_styles'],
            ['en', 'wysiwyg'],
            ['en', 'wysiwyg_styles'],

            // Update
            ['default', 'wysiwyg'],
            ['default', 'wysiwyg2'],
            ['en', 'wysiwyg_styles'],
            ['en', 'wysiwyg_styles2'],
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
            ->method('getApplicableFieldTypes')
            ->willReturn([
                WYSIWYGTwigFunctionProcessorInterface::FIELD_CONTENT_TYPE,
                WYSIWYGTwigFunctionProcessorInterface::FIELD_STYLES_TYPE,
            ]);
        $processor->expects($this->any())
            ->method('getAcceptedTwigFunctions')
            ->willReturn(['test']);

        $processor->expects($this->atLeast(\count($expectedCalls)))
            ->method('processTwigFunctions')
            ->willReturnCallback(
                function (WYSIWYGProcessedDTO $processedDTO, array $twigFunctionCalls) use (&$expectedCalls) {
                    $processedEntityDTO = $processedDTO->getProcessedEntity();
                    $processedEntity = $processedEntityDTO->getEntity();
                    $this->assertInstanceOf(LocalizedFallbackValue::class, $processedEntity);

                    if (empty($twigFunctionCalls)) {
                        $actualFieldValue = $processedEntityDTO->getFieldName() === 'wysiwyg'
                            ? $processedEntity->getWysiwyg()
                            : $processedEntity->getWysiwygStyle();

                        $this->assertEmpty($actualFieldValue);
                        return false;
                    }

                    $this->assertFalse($processedDTO->isSelfOwner());
                    $this->assertSame('descriptions', $processedDTO->getOwnerEntity()->getFieldName());

                    $this->assertCount(1, $twigFunctionCalls);
                    $this->assertArrayHasKey('test', $twigFunctionCalls);

                    foreach ($twigFunctionCalls['test'] as $args) {
                        $index = \array_search($args, $expectedCalls);
                        $this->assertNotFalse($index, "Index: " . $index);
                        unset($expectedCalls[$index]);
                    }

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
    }
}
