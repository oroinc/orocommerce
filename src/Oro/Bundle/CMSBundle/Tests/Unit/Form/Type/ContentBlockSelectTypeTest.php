<?php

declare(strict_types=1);

namespace Oro\Bundle\CMSBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CMSBundle\Entity\ContentBlock;
use Oro\Bundle\CMSBundle\Form\Type\ContentBlockSelectType;
use Oro\Bundle\FormBundle\Form\Type\Select2EntityType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContentBlockSelectTypeTest extends TestCase
{
    private ContentBlockSelectType $type;

    #[\Override]
    protected function setUp(): void
    {
        $this->type = new ContentBlockSelectType();
    }

    public function testConfigureOptionsWithDefaultValues(): void
    {
        $optionsResolver = $this->createMock(OptionsResolver::class);
        $optionsResolver
            ->expects(self::once())
            ->method('setDefaults')
            ->with(self::callback(function (array $value) {
                self::assertEquals([
                    'class' => ContentBlock::class,
                    'expanded' => false,
                    'multiple' => false,
                    'placeholder' => 'oro.cms.contentblock.choose_content_block.label',
                    'choice_translation_domain' => false,
                    'choice_value' => 'id',
                    'choice_label' => fn () => '',
                ], $value);

                return true;
            }));
        $this->type->configureOptions($optionsResolver);
    }

    public function testGetParent(): void
    {
        $this->assertEquals(Select2EntityType::class, $this->type->getParent());
    }
}
