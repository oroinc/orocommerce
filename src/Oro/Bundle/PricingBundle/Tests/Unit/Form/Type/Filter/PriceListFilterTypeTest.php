<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Form\Type\Filter;

use Oro\Bundle\FilterBundle\Form\Type\Filter\ChoiceFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\EntityFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\FilterType;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Form\Type\Filter\PriceListFilterType;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class PriceListFilterTypeTest extends FormIntegrationTestCase
{
    /** @var ShardManager|\PHPUnit\Framework\MockObject\MockObject */
    private $shardManager;

    /** @var PriceListFilterType */
    private $type;

    protected function setUp(): void
    {
        $this->shardManager = $this->createMock(ShardManager::class);

        $this->type = new PriceListFilterType($this->shardManager);

        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::any())
            ->method('trans')
            ->willReturnCallback(function ($id) {
                return $id . ' (translated)';
            });

        return [
            new PreloadedExtension([
                $this->type,
                new FilterType($translator),
                new ChoiceFilterType($translator),
                new EntityFilterType($translator)
            ], [])
        ];
    }

    /**
     * @dataProvider configureOptionsDataProvider
     */
    public function testConfigureOptions(bool $isShardingEnabled): void
    {
        $resolver = $this->createMock(OptionsResolver::class);

        $this->shardManager->expects($this->once())
            ->method('isShardingEnabled')
            ->willReturn($isShardingEnabled);

        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'field_options' => [
                        'class' => PriceList::class,
                        'choice_label' => 'name'
                    ],
                    'required' => $isShardingEnabled
                ]
            )
            ->willReturnSelf();

        $this->type->configureOptions($resolver);
    }

    public function configureOptionsDataProvider(): array
    {
        return [
            ['isShardingEnabled' => false],
            ['isShardingEnabled' => true]
        ];
    }

    public function testGetParent(): void
    {
        $this->assertEquals(EntityFilterType::class, $this->type->getParent());
    }

    public function testGetBlockPrefix(): void
    {
        $this->assertEquals('oro_type_price_list_filter', $this->type->getBlockPrefix());
    }
}
