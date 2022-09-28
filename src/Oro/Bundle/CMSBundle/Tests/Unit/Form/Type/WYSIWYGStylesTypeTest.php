<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CMSBundle\Form\EventSubscriber\DigitalAssetTwigTagsEventSubscriber;
use Oro\Bundle\CMSBundle\Form\Type\WYSIWYGStylesType;
use Oro\Bundle\CMSBundle\Tools\DigitalAssetTwigTagsConverter;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class WYSIWYGStylesTypeTest extends FormIntegrationTestCase
{
    private DigitalAssetTwigTagsConverter|\PHPUnit\Framework\MockObject\MockObject $digitalAssetTwigTagsConverter;

    private EventSubscriberInterface $eventSubscriber;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->digitalAssetTwigTagsConverter = $this->createMock(DigitalAssetTwigTagsConverter::class);
        $this->digitalAssetTwigTagsConverter->expects(self::any())
            ->method('convertToUrls')
            ->willReturnArgument(0);
        $this->digitalAssetTwigTagsConverter->expects(self::any())
            ->method('convertToTwigTags')
            ->willReturnArgument(0);
        $this->eventSubscriber = new DigitalAssetTwigTagsEventSubscriber($this->digitalAssetTwigTagsConverter);

        parent::setUp();
    }

    protected function getExtensions(): array
    {
        $formType = new WYSIWYGStylesType($this->digitalAssetTwigTagsConverter);
        $formType->setDigitalAssetTwigTagsEventSubscriber($this->eventSubscriber);

        return [
            new PreloadedExtension(
                [
                    WYSIWYGStylesType::class => $formType,
                ],
                []
            ),
        ];
    }

    public function testGetParent(): void
    {
        $type = new WYSIWYGStylesType($this->digitalAssetTwigTagsConverter);
        self::assertEquals(HiddenType::class, $type->getParent());
    }

    public function testSubmit(): void
    {
        $form = $this->factory->create(WYSIWYGStylesType::class);
        $form->submit('h1 { color: black; }');
        self::assertEquals('h1 { color: black; }', $form->getData());
    }

    public function testFinishView(): void
    {
        $view = new FormView();
        $form = $this->factory->create(WYSIWYGStylesType::class);
        $type = new WYSIWYGStylesType($this->digitalAssetTwigTagsConverter);
        $type->finishView($view, $form, []);

        self::assertEquals('wysiwyg_styles', $view->vars['attr']['data-grapesjs-styles']);
    }
}
