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
use Oro\Bundle\CMSBundle\Tools\DigitalAssetTwigTagsConverter;
use Oro\Bundle\CMSBundle\Validator\Constraints\TwigContent;
use Oro\Bundle\CMSBundle\Validator\Constraints\TwigContentValidator;
use Oro\Bundle\CMSBundle\Validator\Constraints\WYSIWYG;
use Oro\Bundle\CMSBundle\Validator\Constraints\WYSIWYGValidator;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\FormBundle\Provider\HtmlTagProvider;
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
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\CollectionValidator;
use Symfony\Component\Validator\ConstraintValidatorFactoryInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Template;
use Twig\TemplateWrapper;

class ContentBlockTypeTest extends FormIntegrationTestCase
{
    /**
     * @var DefaultContentVariantScopesResolver|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $defaultVariantScopesResolver;

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $htmlTagProvider = $this->createMock(HtmlTagProvider::class);
        $purifierScopeProvider = $this->createMock(HTMLPurifierScopeProvider::class);
        $digitalAssetTwigTagsConverter = $this->createMock(DigitalAssetTwigTagsConverter::class);
        $digitalAssetTwigTagsConverter->expects(self::any())
            ->method('convertToUrls')
            ->willReturnArgument(0);
        $digitalAssetTwigTagsConverter->expects(self::any())
            ->method('convertToTwigTags')
            ->willReturnArgument(0);

        return [
            new PreloadedExtension(
                [
                    CollectionType::class => new CollectionType(),
                    ScopeCollectionType::class => new ScopeCollectionTypeStub(),
                    LocalizedFallbackValueCollectionType::class => new LocalizedFallbackValueCollectionTypeStub(),
                    new TextContentVariantCollectionType(),
                    new TextContentVariantType(),
                    WYSIWYGType::class => new WYSIWYGType(
                        $htmlTagProvider,
                        $purifierScopeProvider,
                        $digitalAssetTwigTagsConverter
                    )
                ],
                []
            ),
            $this->getValidatorExtension(true),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getValidators()
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

        $wysiwygConstraint = new WYSIWYG();
        $twigContent = new TwigContent();

        return [
            $wysiwygConstraint->validatedBy() => new WYSIWYGValidator(
                $htmlTagHelper,
                $purifierScopeProvider,
                $translator,
                $logger
            ),
            $twigContent->validatedBy() => new TwigContentValidator($twig)
        ];
    }

    /**
     * @return array
     */
    protected function getTypes()
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
     *
     * @param bool         $isValid
     * @param ContentBlock $existingData
     * @param array        $submittedData
     * @param ContentBlock $expectedData
     */
    public function testSubmit($isValid, $existingData, $submittedData, $expectedData)
    {
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

    /**
     * @return array
     */
    public function submitDataProvider()
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
                        [
                            'content' => 'some_content',
                            'scopes' => [],
                        ]
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
                        [
                            'content' => 'some_content',
                            'scopes' => [],
                        ]
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
                        [
                            'content' => 'some_content',
                            'scopes' => [],
                        ]
                    ],
                ],
                (new ContentBlock())
                    ->setAlias('some_title')
                    ->addTitle((new LocalizedFallbackValue())->setString('new_block_node_title'))
                    ->setEnabled(true)
                    ->addContentVariant(
                        (new TextContentVariant())
                            ->setContent('some_content')
                    ),
            ],
            'exist entity' => [
                true,
                (new ContentBlock())
                    ->addContentVariant(
                        (new TextContentVariant())
                            ->setContent('some_content')
                    ),
                [
                    'alias' => 'some_title',
                    'titles' => [['string' => 'changed_block_node_title']],
                    'enabled' => true,
                    'scopes' => [],
                    'contentVariants' => [
                        [
                            'content' => 'some_content',
                            'scopes' => [],
                        ],
                        [
                            'content' => 'some_content2',
                            'scopes' => [],
                        ]
                    ],
                ],
                (new ContentBlock())
                    ->setAlias('some_title')
                    ->addTitle((new LocalizedFallbackValue())->setString('changed_block_node_title'))
                    ->setEnabled(true)
                    ->addContentVariant(
                        (new TextContentVariant())
                            ->setContent('some_content')
                    )
                    ->addContentVariant(
                        (new TextContentVariant())
                            ->setContent('some_content2')
                    ),
            ]
        ];
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|ConstraintValidatorFactoryInterface
     */
    protected function getConstraintValidatorFactory()
    {
        /* @var $factory \PHPUnit\Framework\MockObject\MockObject|ConstraintValidatorFactoryInterface */
        $factory = $this->createMock(ConstraintValidatorFactoryInterface::class);
        $factory->expects($this->any())
            ->method('getInstance')
            ->willReturnCallback(
                function (Constraint $constraint) {
                    $className = $constraint->validatedBy();

                    if ($className === 'doctrine.orm.validator.unique') {
                        $this->validators[$className] = $this->getMockBuilder(UniqueEntityValidator::class)
                            ->disableOriginalConstructor()
                            ->getMock();
                    }

                    if (!isset($this->validators[$className]) || $className === CollectionValidator::class) {
                        $this->validators[$className] = new $className();
                    }

                    return $this->validators[$className];
                }
            );

        return $factory;
    }
}
