<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CMSBundle\Entity\ContentBlock;
use Oro\Bundle\CMSBundle\Entity\TextContentVariant;
use Oro\Bundle\CMSBundle\Form\Type\TextContentVariantCollectionType;
use Oro\Bundle\CMSBundle\Form\Type\TextContentVariantType;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\FormBundle\Form\Type\OroRichTextType;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\ScopeBundle\Form\Type\ScopeCollectionType;
use Oro\Bundle\ScopeBundle\Tests\Unit\Form\Type\Stub\ScopeCollectionTypeStub;
use Oro\Bundle\CMSBundle\Form\Type\ContentBlockType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizedFallbackValueCollectionTypeStub;

use Oro\Component\Testing\Unit\FormIntegrationTestCase;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntityValidator;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorFactoryInterface;

class ContentBlockTypeTest extends FormIntegrationTestCase
{
    /**
     * @var ContentBlockType
     */
    protected $type;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->type = new ContentBlockType();
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        unset($this->type);
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        /**
         * @var \Oro\Bundle\ConfigBundle\Config\ConfigManager|\PHPUnit_Framework_MockObject_MockObject $configManager
         */
        $configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $htmlTagProvider = $this->createMock('Oro\Bundle\FormBundle\Provider\HtmlTagProvider');
        $htmlTagProvider->expects($this->any())
            ->method('getAllowedElements')
            ->willReturn(['br', 'a']);

        return [
            new PreloadedExtension(
                [
                    CollectionType::NAME => new CollectionType(),
                    ScopeCollectionType::NAME => new ScopeCollectionTypeStub(),
                    LocalizedFallbackValueCollectionType::NAME => new LocalizedFallbackValueCollectionTypeStub(),
                    new TextContentVariantCollectionType(),
                    new TextContentVariantType(),
                    OroRichTextType::NAME => new OroRichTextType($configManager, $htmlTagProvider),
                ],
                []
            ),
            $this->getValidatorExtension(true),
        ];
    }

    public function testBuildForm()
    {
        $form = $this->factory->create($this->type);

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
        $form = $this->factory->create($this->type, $existingData);

        $this->assertEquals($existingData, $form->getData());

        $form->submit($submittedData);

        $this->assertEquals($isValid, $form->isValid());

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
     * @return \PHPUnit_Framework_MockObject_MockObject|ConstraintValidatorFactoryInterface
     */
    protected function getConstraintValidatorFactory()
    {
        /* @var $factory \PHPUnit_Framework_MockObject_MockObject|ConstraintValidatorFactoryInterface */
        $factory = $this->createMock('Symfony\Component\Validator\ConstraintValidatorFactoryInterface');
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

                    if (!isset($this->validators[$className]) ||
                        $className === 'Symfony\Component\Validator\Constraints\CollectionValidator'
                    ) {
                        $this->validators[$className] = new $className();
                    }

                    return $this->validators[$className];
                }
            );

        return $factory;
    }
}
