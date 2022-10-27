<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Form\Type\PageType;
use Oro\Bundle\CMSBundle\Form\Type\WYSIWYGType;
use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizedFallbackValueCollectionTypeStub;
use Oro\Bundle\RedirectBundle\Form\Type\LocalizedSlugType;
use Oro\Bundle\RedirectBundle\Form\Type\LocalizedSlugWithRedirectType;
use Oro\Bundle\RedirectBundle\Helper\ConfirmSlugChangeFormHelper;
use Oro\Bundle\RedirectBundle\Tests\Unit\Form\Type\Stub\LocalizedSlugTypeStub;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\PreloadedExtension;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Validator\Type\FormTypeValidatorExtension;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class PageTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;
    use WysiwygAwareTestTrait;

    private const PAGE_ID = 7;

    /** @var UrlGeneratorInterface|MockObject */
    private $urlGenerator;

    /** @var PageType */
    private $type;

    protected function setUp(): void
    {
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->expects($this->any())
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);

        $this->type = new PageType($this->urlGenerator);

        $this->factory = Forms::createFormFactoryBuilder()
            ->addExtensions($this->getExtensions())
            ->addTypeExtension(new FormTypeValidatorExtension($validator))
            ->getFormFactory();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        $metaData = $this->createMock(ClassMetadata::class);
        $metaData->expects($this->any())
            ->method('getSingleIdentifierFieldName')
            ->willReturn('id');

        $em = $this->createMock(EntityManager::class);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);

        $em->expects($this->any())
            ->method('getClassMetadata')
            ->willReturn($metaData);

        $entityIdentifierType = new EntityIdentifierType($registry);

        $confirmSlugChangeFormHelper = $this->createMock(ConfirmSlugChangeFormHelper::class);

        return [
            new PreloadedExtension(
                [
                    $this->type,
                    EntityIdentifierType::class => $entityIdentifierType,
                    'text' => new TextType(),
                    WYSIWYGType::class => $this->createWysiwygType(),
                    LocalizedFallbackValueCollectionType::class => new LocalizedFallbackValueCollectionTypeStub(),
                    LocalizedSlugType::class => new LocalizedSlugTypeStub(),
                    LocalizedSlugWithRedirectType::class => new LocalizedSlugWithRedirectType(
                        $confirmSlugChangeFormHelper
                    ),
                ],
                []
            )
        ];
    }

    public function testBuildForm()
    {
        $form = $this->factory->create(PageType::class);
        $this->assertTrue($form->has('titles'));
        $this->assertTrue($form->has('content'));
        $this->assertTrue($form->has('slugPrototypesWithRedirect'));
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(['data_class' => Page::class, 'csrf_token_id' => 'cms_page']);

        $this->type->configureOptions($resolver);
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(PageType::NAME, $this->type->getBlockPrefix());
    }

    /**
     * @dataProvider submitDataProviderNew
     */
    public function testSubmitNew(mixed $submittedData, mixed $expectedData)
    {
        $defaultData = new Page();

        $form = $this->factory->create(PageType::class, $defaultData, []);

        $this->assertEquals($defaultData, $form->getData());
        $this->assertEquals($defaultData, $form->getViewData());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());

        $this->assertEquals($expectedData, $form->getData());
    }

    public function submitDataProviderNew(): array
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
     * @dataProvider submitDataProviderUpdate
     */
    public function testSubmitUpdate(mixed $defaultData, mixed $submittedData, mixed $expectedData)
    {
        $existingPage = new Page();
        $existingPage->addTitle((new LocalizedFallbackValue())->setString($defaultData['titles']));
        $existingPage->setContent($defaultData['content']);
        $existingPage->addSlugPrototype((new LocalizedFallbackValue())->setString('slug'));

        $defaultData = $existingPage;

        $form = $this->factory->create(PageType::class, $defaultData, []);

        $this->assertEquals($defaultData, $form->getData());
        $this->assertEquals($existingPage, $form->getViewData());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());

        $this->assertEquals($expectedData, $form->getData());
    }

    public function submitDataProviderUpdate(): array
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

    public function testSubmitUpdateWithDraft()
    {
        $existingPage = new Page();
        $existingPage->setDraftUuid('some_uuid');

        $expectedPage = clone $existingPage;
        $expectedPage->addTitle((new LocalizedFallbackValue())->setString('Third test page'));
        $expectedPage->setContent('Page content');

        $form = $this->factory->create(PageType::class, $existingPage, []);
        $form->submit([
            'titles' => [['string' => 'Third test page']],
            'content' => 'Page content',
            'slugPrototypesWithRedirect' => [
                'slugPrototypes' => [['string' => 'slug']],
                'createRedirect' => true,
            ]
        ]);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());

        $this->assertEquals($expectedPage, $form->getData());
    }

    public function testGenerateChangedSlugsUrlOnPresetData()
    {
        $generatedUrl = '/some/url';
        $this->urlGenerator->expects($this->once())
            ->method('generate')
            ->with('oro_cms_page_get_changed_urls', ['id' => 1])
            ->willReturn($generatedUrl);

        $existingData = $this->getEntity(Page::class, [
            'id' => 1,
            'slugPrototypes' => new ArrayCollection([$this->getEntity(LocalizedFallbackValue::class)])
        ]);

        $form = $this->factory->create(PageType::class, $existingData);
        $formView = $form->createView();

        $this->assertArrayHasKey('slugPrototypesWithRedirect', $formView->children);
        $this->assertEquals(
            $generatedUrl,
            $formView->children['slugPrototypesWithRedirect']
                ->vars['confirm_slug_change_component_options']['changedSlugsUrl']
        );
    }

    public function testPreSetDataListener(): void
    {
        $event = $this->createMock(FormEvent::class);
        $event->expects($this->once())
            ->method('getData')
            ->willReturn($page = $this->createMock(Page::class));

        $page->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn($pageId = 1);

        $this->urlGenerator->expects($this->once())
            ->method('generate')
            ->with('oro_cms_page_get_changed_urls', ['id' => $pageId])
            ->willReturn($url = '/sample/url');

        $event->expects($this->once())
            ->method('getForm')
            ->willReturn($form = $this->createMock(FormInterface::class));

        $form->expects($this->once())
            ->method('add')
            ->with(
                'slugPrototypesWithRedirect',
                LocalizedSlugWithRedirectType::class,
                [
                    'label' => 'oro.cms.page.slug_prototypes.label',
                    'required' => false,
                    'source_field' => 'titles',
                    'get_changed_slugs_url' => $url
                ]
            );

        $this->type->preSetDataListener($event);
    }

    public function testPreSetDataListenerWhenNoData(): void
    {
        $event = $this->createMock(FormEvent::class);
        $event->expects($this->once())
            ->method('getData')
            ->willReturn(null);
        $event->expects($this->never())
            ->method('getForm');

        $this->type->preSetDataListener($event);
    }

    public function testPreSetDataListenerWhenNoPageId(): void
    {
        $event = $this->createMock(FormEvent::class);
        $event->expects($this->once())
            ->method('getData')
            ->willReturn($page = $this->createMock(Page::class));

        $page->expects($this->once())
            ->method('getId')
            ->willReturn(null);

        $event->expects($this->never())
            ->method('getForm');

        $this->type->preSetDataListener($event);
    }
}
