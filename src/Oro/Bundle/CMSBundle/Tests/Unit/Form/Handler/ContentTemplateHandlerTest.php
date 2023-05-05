<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Form\Handler;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CMSBundle\Entity\ContentTemplate;
use Oro\Bundle\CMSBundle\Form\Handler\ContentTemplateHandler;
use Oro\Bundle\TagBundle\Entity\Tag;
use Oro\Bundle\TagBundle\Entity\TagManager;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class ContentTemplateHandlerTest extends \PHPUnit\Framework\TestCase
{
    private const FORM_NAME = 'test_content_type_form';

    private const FORM_DATA = [
        'name' => 'Test',
        'content' => 'Test',
        'enabled' => true,
        'tags' => null,
        'updatedAtSet' => null,
        'owner' => null,
        'organization' => null,
    ];

    private EntityManager|\PHPUnit\Framework\MockObject\MockObject $manager;

    private TagManager|\PHPUnit\Framework\MockObject\MockObject $tagManager;

    private Request $request;

    private ContentTemplate $contentTemplate;

    private ContentTemplateHandler $handler;

    protected function setUp(): void
    {
        $this->manager = $this->createMock(EntityManager::class);
        $this->tagManager = $this->createMock(TagManager::class);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects(self::any())
            ->method('getManagerForClass')
            ->with(ContentTemplate::class)
            ->willReturn($this->manager);

        $this->handler = new ContentTemplateHandler($this->tagManager, $registry);

        $this->request = new Request([], [self::FORM_NAME => self::FORM_DATA]);

        $this->contentTemplate = (new ContentTemplate())
            ->setName(self::FORM_DATA['name'])
            ->setContent(self::FORM_DATA['content'])
            ->setEnabled(self::FORM_DATA['enabled']);
    }

    /**
     * @dataProvider normalMethodsDataProvider
     */
    public function testProcessInvalidForm(string $method): void
    {
        $form = $this->getFormMock(
            new ContentTemplate(),
            new ArrayCollection([]),
            false
        );

        $this->request->initialize(
            [],
            [
                self::FORM_NAME => [
                    'name' => '',
                    'content' => '',
                    'enabled' => '',
                ],
            ]
        );
        $this->request->setMethod($method);

        $this->manager->expects(self::never())
            ->method('persist');

        $this->manager->expects(self::never())
            ->method('flush');

        $this->expectExceptionObject(
            new \InvalidArgumentException(
                sprintf('Argument $data was expected to be an instance of %s', ContentTemplate::class)
            )
        );
        $this->handler->process(null, $form, $this->request);
    }

    /**
     * @dataProvider normalMethodsDataProvider
     */
    public function testProcessValidFormWithTags(string $method): void
    {
        $tags = new ArrayCollection([new Tag('test')]);

        $this->request->initialize(
            [],
            [
                self::FORM_NAME => array_merge(
                    self::FORM_DATA,
                    ['tags' => $tags]
                ),
            ]
        );

        $form = $this->getFormMock(
            $this->contentTemplate,
            $tags
        );

        $this->request->setMethod($method);

        $this->manager->expects(self::once())
            ->method('persist')
            ->with($this->contentTemplate);

        $this->manager->expects(self::once())
            ->method('flush');

        $this->tagManager->expects(self::once())
            ->method('setTags')
            ->with($this->contentTemplate, $tags);

        $this->tagManager->expects(self::once())
            ->method('saveTagging')
            ->with($this->contentTemplate);

        self::assertTrue(
            $this->handler->process(
                $this->contentTemplate,
                $form,
                $this->request
            )
        );
    }

    /**
     * @dataProvider normalMethodsDataProvider
     */
    public function testProcessValidFormWithoutTags(string $method): void
    {
        $tags = new ArrayCollection();
        $form = $this->getFormMock($this->contentTemplate, $tags);

        $this->request->setMethod($method);

        $this->manager->expects(self::once())
            ->method('persist')
            ->with($this->contentTemplate);

        $this->manager->expects(self::once())
            ->method('flush');

        $this->tagManager->expects(self::once())
            ->method('setTags')
            ->with($this->contentTemplate, $tags);

        $this->tagManager->expects(self::once())
            ->method('saveTagging')
            ->with($this->contentTemplate);

        self::assertTrue(
            $this->handler->process(
                $this->contentTemplate,
                $form,
                $this->request
            )
        );
    }

    /**
     * @dataProvider badMethodsDataProvider
     */
    public function testProcessValidFormWithBadRequestMethod(string $method): void
    {
        $form = $this->getFormMock(
            null,
            new ArrayCollection(),
            false,
            false
        );
        $this->request->setMethod($method);

        $this->manager->expects(self::never())
            ->method('persist');

        $this->manager->expects(self::never())
            ->method('flush');

        $this->tagManager->expects(self::never())
            ->method('setTags');

        $this->tagManager->expects(self::never())
            ->method('saveTagging');

        self::assertFalse(
            $this->handler->process(
                $this->contentTemplate,
                $form,
                $this->request
            )
        );
    }

    public function normalMethodsDataProvider(): array
    {
        return [
            [
                'method' => Request::METHOD_POST,
            ],
            [
                'method' => Request::METHOD_PUT,
            ],
        ];
    }

    public function badMethodsDataProvider(): array
    {
        return [
            [
                'method' => Request::METHOD_GET,
            ],
            [
                'method' => Request::METHOD_PATCH,
            ],
            [
                'method' => Request::METHOD_DELETE,
            ],
        ];
    }

    private function getFormMock(
        ?ContentTemplate $return,
        ArrayCollection $tags,
        bool $isValid = true,
        bool $isSubmitted = true
    ): FormInterface|\PHPUnit\Framework\MockObject\MockObject {
        $tagsMock = $this->createMock(FormInterface::class);

        $tagsMock
            ->expects(self::any())
            ->method('getData')
            ->willReturn($tags);

        $form = $this->createMock(FormInterface::class);

        $form
            ->expects(self::any())
            ->method('getName')
            ->willReturn(self::FORM_NAME);

        $form
            ->expects(self::any())
            ->method('isValid')
            ->willReturn($isValid);

        $form
            ->expects(self::any())
            ->method('isSubmitted')
            ->willReturn($isSubmitted);

        $form
            ->expects(self::any())
            ->method('get')
            ->with('tags')
            ->willReturn($tagsMock);

        $form
            ->expects(self::any())
            ->method('getData')
            ->willReturn($return);

        return $form;
    }
}
