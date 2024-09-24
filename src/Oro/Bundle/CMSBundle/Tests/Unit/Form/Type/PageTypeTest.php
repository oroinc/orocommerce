<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Form\Type\PageType;
use Oro\Bundle\CMSBundle\Form\Type\WYSIWYGType;
use Oro\Bundle\CMSBundle\Tests\Unit\Entity\Stub\Page as PageStub;
use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;
use Oro\Bundle\FormBundle\Tests\Unit\Stub\TooltipFormExtensionStub;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizedFallbackValueCollectionTypeStub;
use Oro\Bundle\RedirectBundle\Form\Type\LocalizedSlugType;
use Oro\Bundle\RedirectBundle\Form\Type\LocalizedSlugWithRedirectType;
use Oro\Bundle\RedirectBundle\Helper\ConfirmSlugChangeFormHelper;
use Oro\Bundle\RedirectBundle\Model\SlugPrototypesWithRedirect;
use Oro\Bundle\RedirectBundle\Tests\Unit\Form\Type\Stub\LocalizedSlugTypeStub;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\PreloadedExtension;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\DataCollector\Proxy\ResolvedTypeDataCollectorProxy;
use Symfony\Component\Form\Extension\Validator\Type\FormTypeValidatorExtension;
use Symfony\Component\Form\FormBuilder;
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

    private UrlGeneratorInterface|MockObject $urlGenerator;

    private PageType $type;

    #[\Override]
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

    #[\Override]
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
                [
                    FormType::class => [new TooltipFormExtensionStub($this)]
                ]
            )
        ];
    }

    public function testBuildForm(): void
    {
        $form = $this->factory->create(PageType::class);
        $this->assertTrue($form->has('titles'));
        $this->assertTrue($form->has('createUrlSlug'));
        $this->assertTrue($form->has('content'));
        $this->assertTrue($form->has('slugPrototypesWithRedirect'));
        $this->assertTrue($form->has('doNotRenderTitle'));
    }

    public function testConfigureOptions(): void
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(['data_class' => Page::class, 'csrf_token_id' => 'cms_page']);

        $this->type->configureOptions($resolver);
    }

    public function testGetBlockPrefix(): void
    {
        $this->assertEquals(PageType::NAME, $this->type->getBlockPrefix());
    }

    /**
     * @dataProvider submitDataProviderNew
     */
    public function testSubmitNew(mixed $submittedData, mixed $expectedData): void
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
        $page->setDoNotRenderTitle(true);

        $pageWithoutRedirect = clone $page;
        $pageWithoutRedirect->setSlugPrototypesWithRedirect(clone $page->getSlugPrototypesWithRedirect());
        $pageWithoutRedirect->getSlugPrototypesWithRedirect()->setCreateRedirect(false);
        $pageWithoutRedirect->setDoNotRenderTitle(false);

        $pageWithoutUrlSlug = new Page();
        $pageWithoutUrlSlug->addTitle((new LocalizedFallbackValue())->setString('Page without url slug'));
        $pageWithoutUrlSlug->setContent('Page without url slug content');
        $pageWithoutUrlSlug->setSlugPrototypesWithRedirect(
            new SlugPrototypesWithRedirect(new ArrayCollection([]), false)
        );

        return [
            'new page with create redirect' => [
                'submittedData' => [
                    'titles' => [['string' => 'First test page']],
                    'createUrlSlug' => true,
                    'content' => 'Page content',
                    'slugPrototypesWithRedirect' => [
                        'slugPrototypes' => [['string' => 'slug']],
                        'createRedirect' => true,
                    ],
                    'doNotRenderTitle' => true
                ],
                'expectedData' => $page,
            ],
            'new page without create redirect' => [
                'submittedData' => [
                    'titles' => [['string' => 'First test page']],
                    'createUrlSlug' => true,
                    'content' => 'Page content',
                    'slugPrototypesWithRedirect' => [
                        'slugPrototypes' => [['string' => 'slug']],
                        'createRedirect' => false,
                    ],
                    'doNotRenderTitle' => false
                ],
                'expectedData' => $pageWithoutRedirect,
            ],
            'new page without url slug' => [
                'submittedData' => [
                    'titles' => [['string' => 'Page without url slug']],
                    'createUrlSlug' => false,
                    'content' => 'Page without url slug content',
                    'doNotRenderTitle' => false
                ],
                'expectedData' => $pageWithoutUrlSlug,
            ],
        ];
    }

    /**
     * @dataProvider submitDataProviderUpdate
     */
    public function testSubmitUpdate(mixed $defaultData, mixed $submittedData, mixed $expectedData): void
    {
        $existingPage = new Page();
        $existingPage->addTitle((new LocalizedFallbackValue())->setString($defaultData['titles'][0]['string']));
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

        $pageWithoutUrlSlug = new Page();
        $pageWithoutUrlSlug->addTitle((new LocalizedFallbackValue())->setString('Updated page without url slug'));
        $pageWithoutUrlSlug->setContent('Updated page without content');
        $pageWithoutUrlSlug->setSlugPrototypesWithRedirect(
            new SlugPrototypesWithRedirect(new ArrayCollection([]), false)
        );

        return [
            'update page' => [
                'defaultData' => [
                    'titles' => [['string' => 'First test page']],
                    'createUrlSlug' => true,
                    'content' => 'Page content',
                    'slugs'  => [['string' => 'slug']],
                ],
                'submittedData' => [
                    'titles' => [['string' => 'Updated first test page']],
                    'createUrlSlug' => true,
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
                    'createUrlSlug' => true,
                    'content' => 'Page content',
                    'slugs'  => [['string' => 'slug']],
                ],
                'submittedData' => [
                    'titles' => [['string' => 'Updated first test page']],
                    'createUrlSlug' => true,
                    'content' => 'Updated page content',
                    'slugPrototypesWithRedirect' => [
                        'slugPrototypes' => [['string' => 'slug-updated']],
                        'createRedirect' => false,
                    ],
                ],
                'expectedData' => $pageWithoutRedirect,
            ],
            'update page without url slug' => [
                'defaultData' => [
                    'titles' => [['string' => 'First test page']],
                    'createUrlSlug' => false,
                    'content' => 'Page content',
                ],
                'submittedData' => [
                    'titles' => [['string' => 'Updated first test page']],
                    'createUrlSlug' => true,
                    'content' => 'Updated page content',
                    'slugPrototypesWithRedirect' => [
                        'slugPrototypes' => [['string' => 'slug-updated']],
                        'createRedirect' => false,
                    ],
                ],
                'expectedData' => $pageWithoutRedirect,
            ],
            'update page from page with url slug to page without url slug' => [
                'defaultData' => [
                    'titles' => [['string' => 'Updated page without url slug']],
                    'createUrlSlug' => true,
                    'content' => 'Updated page without content',
                    'slugs'  => [['string' => 'slug']],
                ],
                'submittedData' => [
                    'titles' => [['string' => 'Updated page without url slug']],
                    'createUrlSlug' => false,
                    'content' => 'Updated page without content',
                ],
                'expectedData' => $pageWithoutUrlSlug,
            ],
        ];
    }

    public function testSubmitUpdateWithDraft(): void
    {
        $existingPage = new Page();
        $existingPage->setDraftUuid('some_uuid');

        $expectedPage = clone $existingPage;
        $expectedPage->addTitle((new LocalizedFallbackValue())->setString('Third test page'));
        $expectedPage->setContent('Page content');

        $form = $this->factory->create(PageType::class, $existingPage, []);
        $form->submit([
            'titles' => [['string' => 'Third test page']],
            'createUrlSlug' => true,
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

    public function testGenerateChangedSlugsUrlOnPresetData(): void
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

        $this->assertTrue($form->get('createUrlSlug')->getData());
        $this->assertArrayHasKey('slugPrototypesWithRedirect', $formView->children);
        $this->assertEquals(
            $generatedUrl,
            $formView->children['slugPrototypesWithRedirect']
                ->vars['confirm_slug_change_component_options']['changedSlugsUrl']
        );
    }

    public function testPreSetData(): void
    {
        $form = $this->createMock(FormInterface::class);

        $event = new FormEvent($form, new PageStub(1));

        $this->urlGenerator->expects($this->once())
            ->method('generate')
            ->with('oro_cms_page_get_changed_urls', ['id' => 1])
            ->willReturn($url = '/sample/url');

        $type = $this->createMock(ResolvedTypeDataCollectorProxy::class);
        $type->expects($this->exactly(2))
            ->method('getInnerType')
            ->willReturn(
                new CheckboxType(),
                new LocalizedSlugWithRedirectType($this->createMock(ConfirmSlugChangeFormHelper::class))
            );

        $config = $this->createMock(FormBuilder::class);
        $config->expects($this->exactly(2))
            ->method('getType')
            ->willReturn($type);

        $childForm = $this->createMock(FormInterface::class);
        $childForm->expects($this->exactly(2))
            ->method('getConfig')
            ->willReturn($config);

        $form->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(['createUrlSlug'], ['slugPrototypesWithRedirect'])
            ->willReturn($childForm);

        $form->expects($this->exactly(2))
            ->method('add')
            ->withConsecutive(
                ['createUrlSlug', CheckboxType::class, ['data' => false]],
                ['slugPrototypesWithRedirect', LocalizedSlugWithRedirectType::class, ['get_changed_slugs_url' => $url]]
            );

        $this->type->preSetData($event);
    }

    public function testPreSetDataWhenNoData(): void
    {
        $event = $this->createMock(FormEvent::class);
        $event->expects($this->once())
            ->method('getData')
            ->willReturn(null);
        $event->expects($this->never())
            ->method('getForm');

        $this->type->preSetData($event);
    }

    public function testPreSetDataWhenNoPageId(): void
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

        $this->type->preSetData($event);
    }
}
