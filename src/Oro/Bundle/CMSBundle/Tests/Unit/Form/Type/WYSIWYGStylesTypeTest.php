<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CMSBundle\Form\Type\WYSIWYGStylesType;
use Oro\Bundle\CMSBundle\Tools\DigitalAssetTwigTagsConverter;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class WYSIWYGStylesTypeTest extends FormIntegrationTestCase
{
    /** @var DigitalAssetTwigTagsConverter|\PHPUnit\Framework\MockObject\MockObject */
    private DigitalAssetTwigTagsConverter $digitalAssetTwigTagsConverter;

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

        parent::setUp();
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    WYSIWYGStylesType::class => new WYSIWYGStylesType($this->digitalAssetTwigTagsConverter)
                ],
                []
            )
        ];
    }

    public function testGetParent()
    {
        $type = new WYSIWYGStylesType($this->digitalAssetTwigTagsConverter);
        $this->assertEquals(HiddenType::class, $type->getParent());
    }

    public function testSubmit()
    {
        $form = $this->factory->create(WYSIWYGStylesType::class);
        $form->submit('h1 { color: black; }');
        $this->assertEquals('h1 { color: black; }', $form->getData());
    }

    public function testFinishView()
    {
        $view = new FormView();
        $form = $this->factory->create(WYSIWYGStylesType::class);
        $type = new WYSIWYGStylesType($this->digitalAssetTwigTagsConverter);
        $type->finishView($view, $form, []);

        $this->assertEquals('wysiwyg_styles', $view->vars['attr']['data-grapesjs-styles']);
    }
}
