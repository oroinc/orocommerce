<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Form\Type;

use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Validator\Type\FormTypeValidatorExtension;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\ConstraintViolationList;

use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;
use Oro\Bundle\FormBundle\Form\Type\OroRichTextType;
use Oro\Bundle\CMSBundle\Form\Type\PageType;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizedFallbackValueCollectionTypeStub;

class PageTypeTest extends FormIntegrationTestCase
{
    /**
     * @var PageType
     */
    protected $type;

    protected function setUp()
    {
        /**
         * @var \Symfony\Component\Validator\ValidatorInterface|\PHPUnit_Framework_MockObject_MockObject $validator
         */
        $validator = $this->getMock('\Symfony\Component\Validator\ValidatorInterface');
        $validator->expects($this->any())
            ->method('validate')
            ->will($this->returnValue(new ConstraintViolationList()));

        $this->factory = Forms::createFormFactoryBuilder()
            ->addExtensions($this->getExtensions())
            ->addTypeExtension(new FormTypeValidatorExtension($validator))
            ->getFormFactory();

        $this->type = new PageType();
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $metaData = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $metaData->expects($this->any())
            ->method('getSingleIdentifierFieldName')
            ->will($this->returnValue('id'));

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        /**
         * @var \Doctrine\Common\Persistence\ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject $registry
         */
        $registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');

        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->will($this->returnValue($em));

        $em->expects($this->any())
            ->method('getClassMetadata')
            ->will($this->returnValue($metaData));

        $entityIdentifierType = new EntityIdentifierType($registry);

        /**
         * @var \Oro\Bundle\ConfigBundle\Config\ConfigManager|\PHPUnit_Framework_MockObject_MockObject $configManager
         */
        $configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $htmlTagProvider = $this->getMock('Oro\Bundle\FormBundle\Provider\HtmlTagProvider');
        $htmlTagProvider->expects($this->any())
            ->method('getAllowedElements')
            ->willReturn(['br', 'a']);

        return [
            new PreloadedExtension(
                [
                    EntityIdentifierType::NAME => $entityIdentifierType,
                    'text' => new TextType(),
                    OroRichTextType::NAME => new OroRichTextType($configManager, $htmlTagProvider),
                    LocalizedFallbackValueCollectionType::NAME => new LocalizedFallbackValueCollectionTypeStub(),
                ],
                []
            )
        ];
    }

    public function testBuildForm()
    {
        $builder = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $builder->expects($this->at(0))
            ->method('add')
            ->with(
                'titles',
                LocalizedFallbackValueCollectionType::NAME,
                [
                    'label'    => 'oro.cms.page.titles.label',
                    'required' => true,
                    'constraints' => [new NotBlank()],
                ]
            )
            ->will($this->returnSelf());
        $builder->expects($this->at(1))
            ->method('add')
            ->with(
                'content',
                OroRichTextType::NAME,
                [
                    'label' => 'oro.cms.page.content.label',
                    'required' => false,
                    'wysiwyg_options' => [
                        'statusbar' => true,
                        'resize' => true
                    ]
                ]
            )
            ->will($this->returnSelf());
        $builder->expects($this->at(2))
                ->method('add')
                ->with(
                    'slugs',
                    LocalizedFallbackValueCollectionType::NAME,
                    [
                        'label'    => 'oro.cms.page.slugs.label',
                        'required' => true,
                        'options'  => ['constraints' => [new NotBlank()]],
                    ]
                )
                ->will($this->returnSelf());

        $this->type->buildForm($builder, []);
    }

    public function testSetDefaultOptions()
    {
        $resolver = $this->getMockBuilder('Symfony\Component\OptionsResolver\OptionsResolver')
            ->disableOriginalConstructor()
            ->getMock();
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'data_class' => Page::class
                ]
            );

        $this->type->configureOptions($resolver);
    }

    public function testGetName()
    {
        $this->assertEquals(PageType::NAME, $this->type->getName());
    }

    /**
     * @param array $options
     * @param mixed $defaultData
     * @param mixed $submittedData
     * @param mixed $expectedData
     * @dataProvider submitDataProvider
     */
    public function testSubmit(array $options, $defaultData, $submittedData, $expectedData)
    {
        if ($defaultData) {
            $existingPage = new Page();
            $existingPage->addTitle((new LocalizedFallbackValue())->setString($defaultData['titles']));
            $existingPage->setContent($defaultData['content']);
            $existingPage->addSlug((new LocalizedFallbackValue())->setString('slug'));

            $defaultData = $existingPage;
        }

        $form = $this->factory->create($this->type, $defaultData, $options);

        $this->assertEquals($defaultData, $form->getData());
        if (isset($existingPage)) {
            $this->assertEquals($existingPage, $form->getViewData());
        } else {
            $this->assertNull($form->getViewData());
        }

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());

        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array
     */
    public function submitDataProvider()
    {
        $new_page = new Page();
        $new_page->addTitle((new LocalizedFallbackValue())->setString('First test page'));
        $new_page->setContent('Page content');
        $new_page->addSlug((new LocalizedFallbackValue())->setString('slug'));
        $updated_page = new Page();
        $updated_page->addTitle((new LocalizedFallbackValue())->setString('Updated first test page'));
        $updated_page->setContent('Updated page content');
        $updated_page->addSlug((new LocalizedFallbackValue())->setString('slug-updated'));

        return [
            'new page' => [
                'options' => [],
                'defaultData' => null,
                'submittedData' => [
                    'titles' => [['string' => 'First test page']],
                    'content' => 'Page content',
                    'slugs'  => [['string' => 'slug']],
                ],
                'expectedData' => $new_page,
            ],
            'update page' => [
                'options' => [],
                'defaultData' => [
                    'titles' => [['string' => 'First test page']],
                    'content' => 'Page content',
                    'slugs'  => [['string' => 'slug']],
                ],
                'submittedData' => [
                    'titles' => [['string' => 'Updated first test page']],
                    'content' => 'Updated page content',
                    'slugs'  => [['string' => 'slug-updated']],
                ],
                'expectedData' => $updated_page,
            ],
        ];
    }
}
