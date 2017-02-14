<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Form\Type\PageType;
use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;
use Oro\Bundle\FormBundle\Form\Type\OroRichTextType;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizedFallbackValueCollectionTypeStub;
use Oro\Bundle\RedirectBundle\Form\Type\LocalizedSlugType;
use Oro\Bundle\RedirectBundle\Form\Type\LocalizedSlugWithRedirectType;
use Oro\Bundle\RedirectBundle\Helper\ConfirmSlugChangeFormHelper;
use Oro\Bundle\RedirectBundle\Tests\Unit\Form\Type\Stub\LocalizedSlugTypeStub;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Validator\Type\FormTypeValidatorExtension;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Validator\ConstraintViolationList;

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
        $validator = $this->createMock('\Symfony\Component\Validator\ValidatorInterface');
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
        $registry = $this->createMock('Doctrine\Common\Persistence\ManagerRegistry');

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

        $htmlTagProvider = $this->createMock('Oro\Bundle\FormBundle\Provider\HtmlTagProvider');
        $htmlTagProvider->expects($this->any())
            ->method('getAllowedElements')
            ->willReturn(['br', 'a']);

        /** @var ConfirmSlugChangeFormHelper $confirmSlugChangeFormHelper */
        $confirmSlugChangeFormHelper = $this->getMockBuilder(ConfirmSlugChangeFormHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        return [
            new PreloadedExtension(
                [
                    EntityIdentifierType::NAME => $entityIdentifierType,
                    'text' => new TextType(),
                    OroRichTextType::NAME => new OroRichTextType($configManager, $htmlTagProvider),
                    LocalizedFallbackValueCollectionType::NAME => new LocalizedFallbackValueCollectionTypeStub(),
                    LocalizedSlugType::NAME => new LocalizedSlugTypeStub(),
                    LocalizedSlugWithRedirectType::NAME
                        => new LocalizedSlugWithRedirectType($confirmSlugChangeFormHelper),
                ],
                []
            )
        ];
    }

    public function testBuildForm()
    {
        $form = $this->factory->create($this->type);
        $this->assertTrue($form->has('titles'));
        $this->assertTrue($form->has('content'));
        $this->assertTrue($form->has('slugPrototypesWithRedirect'));
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

    public function testGetBlockPrefix()
    {
        $this->assertEquals(PageType::NAME, $this->type->getBlockPrefix());
    }

    /**
     * @param mixed $submittedData
     * @param mixed $expectedData
     * @dataProvider submitDataProviderNew
     */
    public function testSubmitNew($submittedData, $expectedData)
    {
        $defaultData = new Page();

        $form = $this->factory->create($this->type, $defaultData, []);

        $this->assertEquals($defaultData, $form->getData());
        $this->assertEquals($defaultData, $form->getViewData());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());

        $this->assertEquals($expectedData, $form->getData());
    }


    /**
     * @return array
     */
    public function submitDataProviderNew()
    {
        $page = new Page();
        $page->addTitle((new LocalizedFallbackValue())->setString('First test page'));
        $page->setContent('Page content');
        $page->addSlugPrototype((new LocalizedFallbackValue())->setString('slug'));

        $pageWithoutRedirect = clone $page;
        $pageWithoutRedirect->setSlugPrototypesWithRedirect(clone $page->getSlugPrototypesWithRedirect());
        $pageWithoutRedirect->getSlugPrototypesWithRedirect()->setCreateRedirect(false);

        return [
            'new page with create redirect' => [
                'submittedData' => [
                    'titles' => [['string' => 'First test page']],
                    'content' => 'Page content',
                    'slugPrototypesWithRedirect' => [
                        'slugPrototypes' => [['string' => 'slug']],
                        'createRedirect' => true,
                    ],
                ],
                'expectedData' => $page,
            ],
            'new page without create redirect' => [
                'submittedData' => [
                    'titles' => [['string' => 'First test page']],
                    'content' => 'Page content',
                    'slugPrototypesWithRedirect' => [
                        'slugPrototypes' => [['string' => 'slug']],
                        'createRedirect' => false,
                    ],
                ],
                'expectedData' => $pageWithoutRedirect,
            ],
        ];
    }

    /**
     * @param mixed $defaultData
     * @param mixed $submittedData
     * @param mixed $expectedData
     * @dataProvider submitDataProviderUpdate
     */
    public function testSubmitUpdate($defaultData, $submittedData, $expectedData)
    {
        $existingPage = new Page();
        $existingPage->addTitle((new LocalizedFallbackValue())->setString($defaultData['titles']));
        $existingPage->setContent($defaultData['content']);
        $existingPage->addSlugPrototype((new LocalizedFallbackValue())->setString('slug'));

        $defaultData = $existingPage;

        $form = $this->factory->create($this->type, $defaultData, []);

        $this->assertEquals($defaultData, $form->getData());
        $this->assertEquals($existingPage, $form->getViewData());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());

        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitDataProviderUpdate()
    {
        $page = new Page();
        $page->addTitle((new LocalizedFallbackValue())->setString('Updated first test page'));
        $page->setContent('Updated page content');
        $page->addSlugPrototype((new LocalizedFallbackValue())->setString('slug-updated'));

        $pageWithoutRedirect = clone $page;
        $pageWithoutRedirect->setSlugPrototypesWithRedirect(clone $page->getSlugPrototypesWithRedirect());
        $pageWithoutRedirect->getSlugPrototypesWithRedirect()->setCreateRedirect(false);

        return [
            'update page' => [
                'defaultData' => [
                    'titles' => [['string' => 'First test page']],
                    'content' => 'Page content',
                    'slugs'  => [['string' => 'slug']],
                ],
                'submittedData' => [
                    'titles' => [['string' => 'Updated first test page']],
                    'content' => 'Updated page content',
                    'slugPrototypesWithRedirect' => [
                        'slugPrototypes' => [['string' => 'slug-updated']],
                        'createRedirect' => true,
                    ],
                ],
                'expectedData' => $page,
            ],
            'update page without redirect' => [
                'defaultData' => [
                    'titles' => [['string' => 'First test page']],
                    'content' => 'Page content',
                    'slugs'  => [['string' => 'slug']],
                ],
                'submittedData' => [
                    'titles' => [['string' => 'Updated first test page']],
                    'content' => 'Updated page content',
                    'slugPrototypesWithRedirect' => [
                        'slugPrototypes' => [['string' => 'slug-updated']],
                        'createRedirect' => false,
                    ],
                ],
                'expectedData' => $pageWithoutRedirect,
            ],
        ];
    }
}
