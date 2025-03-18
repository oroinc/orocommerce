<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\CMSBundle\Form\Type\ContentWidgetSelectType;
use Oro\Bundle\FormBundle\Form\Type\Select2EntityType;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ContentWidgetSelectTypeTest extends TestCase
{
    private ContentWidgetSelectType $type;

    #[\Override]
    protected function setUp(): void
    {
        $this->type = new ContentWidgetSelectType($this->createMock(ManagerRegistry::class));
    }

    public function testConfigureOptionsWithDefaultValues(): void
    {
        $optionsResolver = $this->createMock(OptionsResolver::class);
        $optionsResolver
            ->expects(self::once())
            ->method('setDefaults')
            ->with(self::callback(function (array $value) {
                self::assertEquals([
                    'class' => ContentWidget::class,
                    'expanded' => false,
                    'multiple' => false,
                    'placeholder' => 'oro.cms.contentwidget.form.choose_content_widget.label',
                    'choice_translation_domain' => false,
                    'choice_value' => 'id',
                    'choice_label' => fn () => '',
                    'widgetTypes' => [],
                    'query_builder' => fn () => '',
                ], $value);

                return true;
            }));
        $this->type->configureOptions($optionsResolver);
    }

    public function testGetParent(): void
    {
        self::assertSame(Select2EntityType::class, $this->type->getParent());
    }
}
