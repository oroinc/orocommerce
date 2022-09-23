<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\CMSBundle\Entity\TabbedContentItem;
use Oro\Bundle\CMSBundle\Form\Type\TabbedContentItemCollectionType;
use Oro\Bundle\CMSBundle\Form\Type\TabbedContentItemType;
use Oro\Bundle\CMSBundle\Form\Type\WYSIWYGType;
use Oro\Bundle\CMSBundle\Tests\Unit\Entity\Stub\TabbedContentItemStub;
use Oro\Bundle\FormBundle\Form\Extension\DataBlockExtension;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;

class TabbedContentItemTypeTest extends FormIntegrationTestCase
{
    use WysiwygAwareTestTrait;

    public function testSubmitNew(): void
    {
        $defaultData = new TabbedContentItem();

        $form = $this->factory->create(
            TabbedContentItemType::class,
            $defaultData,
            ['content_widget' => new ContentWidget()]
        );

        self::assertEquals($defaultData, $form->getData());
        self::assertEquals($defaultData, $form->getViewData());

        $form->submit(['title' => 'sample title', 'itemOrder' => 1, 'content' => 'test content']);
        self::assertTrue($form->isValid());
        self::assertTrue($form->isSynchronized());

        $expected = new TabbedContentItem();
        $expected
            ->setContentWidget(new ContentWidget())
            ->setTitle('sample title')
            ->setItemOrder(1)
            ->setContent('test content');

        self::assertEquals($expected, $form->getData());
    }

    public function testSubmitExisting(): void
    {
        $contentWidget = new ContentWidget();
        $tabbedContentItem = (new TabbedContentItemStub(42))
            ->setTitle('sample title')
            ->setItemOrder(1)
            ->setContent('sample content')
            ->setContentWidget($contentWidget);

        $form = $this->factory->create(
            TabbedContentItemType::class,
            $tabbedContentItem,
            ['content_widget' => $contentWidget]
        );

        self::assertSame($tabbedContentItem, $form->getData());
        self::assertSame($tabbedContentItem, $form->getViewData());

        $form->submit(['title' => 'updated sample title', 'itemOrder' => 42, 'content' => 'updated sample content']);
        self::assertTrue($form->isValid());
        self::assertTrue($form->isSynchronized());

        self::assertSame($tabbedContentItem, $form->getData());
        self::assertEquals('updated sample title', $form->getData()->getTitle());
        self::assertEquals(42, $form->getData()->getItemOrder());
        self::assertEquals('updated sample content', $form->getData()->getContent());
    }

    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [
                    TabbedContentItemCollectionType::class => new TabbedContentItemCollectionType(),
                    TabbedContentItemType::class => new TabbedContentItemType(),
                    WYSIWYGType::class => $this->createWysiwygType(),
                ],
                [
                    FormType::class => [new DataBlockExtension()],
                ]
            ),
        ];
    }
}
