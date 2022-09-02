<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CMSBundle\ContentBlock\DefaultContentVariantScopesResolver;
use Oro\Bundle\CMSBundle\Entity\ContentBlock;
use Oro\Bundle\CMSBundle\Entity\TextContentVariant;
use Oro\Bundle\CMSBundle\Form\Type\ContentBlockType;
use Oro\Bundle\CMSBundle\Form\Type\TextContentVariantCollectionType;
use Oro\Bundle\CMSBundle\Form\Type\TextContentVariantType;
use Oro\Bundle\CMSBundle\Form\Type\WYSIWYGType;
use Oro\Bundle\CMSBundle\Provider\HTMLPurifierScopeProvider;
use Oro\Bundle\CMSBundle\Tests\Unit\Form\Type\Stub\WYSIWYGTypeStub;
use Oro\Bundle\CMSBundle\Validator\Constraints\TwigContentValidator;
use Oro\Bundle\CMSBundle\Validator\Constraints\WYSIWYGValidator;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizedFallbackValueCollectionTypeStub;
use Oro\Bundle\ScopeBundle\Form\Type\ScopeCollectionType;
use Oro\Bundle\ScopeBundle\Tests\Unit\Form\Type\Stub\ScopeCollectionTypeStub;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntityValidator;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Template;
use Twig\TemplateWrapper;

class ContentBlockTypeTest extends FormIntegrationTestCase
{
    /** @var DefaultContentVariantScopesResolver|\PHPUnit\Framework\MockObject\MockObject */
    private $defaultVariantScopesResolver;

    /**
     * {@inheritdoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [
                    CollectionType::class => new CollectionType(),
                    ScopeCollectionType::class => new ScopeCollectionTypeStub(),
                    LocalizedFallbackValueCollectionType::class => new LocalizedFallbackValueCollectionTypeStub(),
                    new TextContentVariantCollectionType(),
                    new TextContentVariantType(),
                    WYSIWYGType::class => new WYSIWYGTypeStub()
                ],
                []
            ),
            $this->getValidatorExtension(true),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getValidators(): array
    {
        $htmlTagHelper = $this->createMock(HtmlTagHelper::class);
        $purifierScopeProvider = $this->createMock(HTMLPurifierScopeProvider::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $template = $this->createMock(Template::class);
        $template->expects($this->any())
            ->method('render')
            ->willReturn('template');

        $twig = $this->createMock(Environment::class);
        $twig->expects($this->any())
            ->method('createTemplate')
            ->willReturn(new TemplateWrapper($twig, $template));

        return [
            'doctrine.orm.validator.unique' => $this->createMock(UniqueEntityValidator::class),
            WYSIWYGValidator::class => new WYSIWYGValidator(
                $htmlTagHelper,
                $purifierScopeProvider,
                $translator,
                $logger
            ),
            TwigContentValidator::class => new TwigContentValidator($twig)
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getTypes(): array
    {
        $this->defaultVariantScopesResolver = $this->createMock(DefaultContentVariantScopesResolver::class);

        return [new ContentBlockType($this->defaultVariantScopesResolver)];
    }

    public function testBuildForm()
    {
        $form = $this->factory->create(ContentBlockType::class);

        $this->assertTrue($form->has('alias'));
        $this->assertTrue($form->has('titles'));
        $this->assertTrue($form->has('scopes'));
        $this->assertTrue($form->has('enabled'));
        $this->assertTrue($form->has('contentVariants'));
    }

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmit(
        bool $isValid,
        ContentBlock $existingData,
        array $submittedData,
        ?ContentBlock $expectedData
    ) {
        $form = $this->factory->create(ContentBlockType::class, $existingData);

        $this->defaultVariantScopesResolver->expects($this->once())
            ->method('resolve');
        $this->assertEquals($existingData, $form->getData());

        $form->submit($submittedData);

        $this->assertEquals($isValid, $form->isValid());
        $this->assertTrue($form->isSynchronized());

        if ($isValid) {
            $this->assertEquals($expectedData, $form->getData());
        }
    }

    public function submitDataProvider(): array
    {
        return [
            'empty_alias' => [
                false,
                new ContentBlock(),
                [
                    'alias' => '',
                    'titles' => [['string' => 'new_block_node_title']],
                    'enabled' => true,
                    'scopes' => [],
                    'contentVariants' => [
                        ['content' => 'some_content', 'scopes' => []]
                    ],
                ],
                null
            ],
            'wrong_alias' => [
                false,
                new ContentBlock(),
                [
                    'alias' => 'some_title//',
                    'titles' => [['string' => 'new_block_node_title']],
                    'enabled' => true,
                    'scopes' => [],
                    'contentVariants' => [
                        ['content' => 'some_content', 'scopes' => []]
                    ],
                ],
                null
            ],
            'new entity' => [
                true,
                new ContentBlock(),
                [
                    'alias' => 'some_title',
                    'titles' => [['string' => 'new_block_node_title']],
                    'enabled' => true,
                    'scopes' => [],
                    'contentVariants' => [
                        ['content' => 'some_content', 'scopes' => []]
                    ],
                ],
                (new ContentBlock())
                    ->setAlias('some_title')
                    ->addTitle((new LocalizedFallbackValue())->setString('new_block_node_title'))
                    ->setEnabled(true)
                    ->addContentVariant((new TextContentVariant())->setContent('some_content')),
            ],
            'exist entity' => [
                true,
                (new ContentBlock())
                    ->addContentVariant((new TextContentVariant())->setContent('some_content')),
                [
                    'alias' => 'some_title',
                    'titles' => [['string' => 'changed_block_node_title']],
                    'enabled' => true,
                    'scopes' => [],
                    'contentVariants' => [
                        ['content' => 'some_content', 'scopes' => []],
                        ['content' => 'some_content2', 'scopes' => []]
                    ],
                ],
                (new ContentBlock())
                    ->setAlias('some_title')
                    ->addTitle((new LocalizedFallbackValue())->setString('changed_block_node_title'))
                    ->setEnabled(true)
                    ->addContentVariant((new TextContentVariant())->setContent('some_content'))
                    ->addContentVariant((new TextContentVariant())->setContent('some_content2')),
            ]
        ];
    }
}
