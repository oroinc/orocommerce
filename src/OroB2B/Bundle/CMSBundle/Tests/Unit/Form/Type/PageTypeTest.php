<?php

namespace OroB2B\Bundle\CMSBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Validator\Type\FormTypeValidatorExtension;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\ConstraintViolationList;

use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;
use Oro\Bundle\FormBundle\Form\Type\OroRichTextType;

use OroB2B\Bundle\CMSBundle\Form\Type\PageType;
use OroB2B\Bundle\CMSBundle\Entity\Page;
use OroB2B\Bundle\CMSBundle\Form\Type\SlugType;
use OroB2B\Bundle\RedirectBundle\Entity\Slug;

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
                    SlugType::NAME => new SlugType()
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
                'parentPage',
                EntityIdentifierType::NAME,
                [
                    'class' => 'OroB2B\Bundle\CMSBundle\Entity\Page',
                    'multiple' => false
                ]
            )
            ->will($this->returnSelf());
        $builder->expects($this->at(1))
            ->method('add')
            ->with(
                'title',
                'text',
                [
                    'label' => 'orob2b.cms.page.title.label',
                    'required' => true,
                    'constraints' => [new NotBlank()],
                ]
            )
            ->will($this->returnSelf());
        $builder->expects($this->at(2))
            ->method('add')
            ->with(
                'content',
                OroRichTextType::NAME,
                [
                    'label' => 'orob2b.cms.page.content.label',
                    'required' => false,
                ]
            )
            ->will($this->returnSelf());

        $this->type->buildForm($builder, []);
    }

    public function testSetDefaultOptions()
    {
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'data_class' => 'OroB2B\Bundle\CMSBundle\Entity\Page',
                    'intention' => 'page',
                    'extra_fields_message' => 'This form should not contain extra fields: "{{ extra_fields }}"'
                ]
            );

        $this->type->setDefaultOptions($resolver);
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
            $this->setId($existingPage, 1);
            $existingPage->setTitle($defaultData['title']);
            $existingPage->setContent($defaultData['content']);

            $existingSlug = new Slug();
            $this->setId($existingSlug, 1);
            $existingSlug->setUrl($defaultData['slug']['slug']);
            $existingPage->setCurrentSlug($existingSlug);

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
        /** @var Page $result */
        $result = $form->getData();
        $this->assertEquals($expectedData['title'], $result->getTitle());
        $this->assertEquals($expectedData['content'], $result->getContent());
        $this->assertEquals($expectedData['slug'], $result->getCurrentSlug()->getUrl());
    }

    /**
     * @param mixed $obj
     * @param mixed $val
     */
    protected function setId($obj, $val)
    {
        $class = new \ReflectionClass($obj);
        $prop  = $class->getProperty('id');
        $prop->setAccessible(true);

        $prop->setValue($obj, $val);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            'new page' => [
                'options' => [],
                'defaultData' => null,
                'submittedData' => [
                    'parentPage' => null,
                    'title' => 'First test page',
                    'content' => 'Page content',
                    'slug' => [
                        'mode' => 'new',
                        'slug' => '/first-page'
                    ]
                ],
                'expectedData' => [
                    'parentPage' => null,
                    'title' => 'First test page',
                    'content' => 'Page content',
                    'mode' => 'new',
                    'slug' => '/first-page'
                ],
            ],
            'update current page without redirect' => [
                'options' => [],
                'defaultData' => [
                    'parentPage' => null,
                    'title' => 'First test page',
                    'content' => 'Page content',
                    'slug' => [
                        'mode' => 'new',
                        'slug' => '/first-page'
                    ]
                ],
                'submittedData' => [
                    'parentPage' => null,
                    'title' => 'Updated first test page',
                    'content' => 'Updated page content',
                    'slug' => [
                        'mode' => 'new',
                        'slug' => '/updated-first-page'
                    ]
                ],
                'expectedData' => [
                    'parentPage' => null,
                    'title' => 'Updated first test page',
                    'content' => 'Updated page content',
                    'slug' => '/updated-first-page'
                ],
            ],
            'update current page with redirect' => [
                'options' => [],
                'defaultData' => [
                    'parentPage' => null,
                    'title' => 'First test page',
                    'content' => 'Page content',
                    'slug' => [
                        'mode' => 'new',
                        'slug' => '/first-page'
                    ]
                ],
                'submittedData' => [
                    'parentPage' => null,
                    'title' => 'Updated first test page',
                    'content' => 'Updated page content',
                    'slug' => [
                        'mode' => 'new',
                        'redirect' => true,
                        'slug' => '/updated-first-page'
                    ]
                ],
                'expectedData' => [
                    'parentPage' => null,
                    'title' => 'Updated first test page',
                    'content' => 'Updated page content',
                    'slug' => '/updated-first-page'
                ],
            ],
            'update current page with old slug' => [
                'options' => [],
                'defaultData' => [
                    'parentPage' => null,
                    'title' => 'First test page',
                    'content' => 'Page content',
                    'slug' => [
                        'mode' => 'old',
                        'slug' => '/first-page'
                    ]
                ],
                'submittedData' => [
                    'parentPage' => null,
                    'title' => 'Updated first test page',
                    'content' => 'Updated page content',
                    'slug' => [
                        'mode' => 'old',
                    ]
                ],
                'expectedData' => [
                    'parentPage' => null,
                    'title' => 'Updated first test page',
                    'content' => 'Updated page content',
                    'slug' => '/first-page'
                ],
            ],
        ];
    }
}
