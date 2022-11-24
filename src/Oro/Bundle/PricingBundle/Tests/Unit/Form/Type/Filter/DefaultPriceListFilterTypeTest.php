<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Form\Type\Filter;

use Oro\Bundle\FilterBundle\Form\Type\Filter\ChoiceFilterType;
use Oro\Bundle\FilterBundle\Tests\Unit\Form\Type\AbstractTypeTestCase;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Form\Type\Filter\DefaultPriceListFilterType;
use Oro\Bundle\PricingBundle\Provider\PriceListProvider;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;

class DefaultPriceListFilterTypeTest extends AbstractTypeTestCase
{
    /** @var PriceListProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $provider;

    /** @var ShardManager|\PHPUnit\Framework\MockObject\MockObject */
    private $shardManager;

    /** @var string */
    private $priceListClass;

    /** @var DefaultPriceListFilterType */
    private $type;

    protected function setUp(): void
    {
        $translator = $this->createMockTranslator();
        $this->shardManager = $this->createMock(ShardManager::class);
        $this->provider = $this->createMock(PriceListProvider::class);
        $this->priceListClass = PriceList::class;

        $this->type = new DefaultPriceListFilterType(
            $translator,
            $this->provider,
            $this->shardManager,
            $this->priceListClass
        );

        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getTestFormType(): AbstractType
    {
        return $this->type;
    }

    public function testGetParent()
    {
        $this->assertEquals(ChoiceFilterType::class, $this->type->getParent());
    }

    /**
     * @dataProvider configureOptionsDataProvider
     */
    public function testConfigureOptions(array $defaultOptions, array $requiredOptions = [])
    {
        $this->shardManager->expects($this->once())
            ->method('isShardingEnabled')
            ->willReturn(true);

        $resolver = $this->createMockOptionsResolver();
        $resolver->expects($this->exactly(2))
            ->method('setDefaults')
            ->withConsecutive(
                [$defaultOptions],
                [
                    [
                        'field_options' => [
                            'class' => $this->priceListClass,
                            'choice_label' => 'name'
                        ],
                        'required' => true
                    ]
                ]
            )
            ->willReturnSelf();

        $this->getTestFormType()->configureOptions($resolver);
    }

    /**
     * @dataProvider configureOptionsDataProvider
     */
    public function testConfigureOptionsWhenEntityNotSharded(array $parentDefaultOptions)
    {
        $defaultOptions = [
            'field_options' => [
                'class' => $this->priceListClass,
                'choice_label' => 'name'
            ],
            'required' => false,
        ];

        $this->shardManager->expects($this->once())
            ->method('isShardingEnabled')
            ->willReturn(false);

        $resolver = $this->createMockOptionsResolver();
        $resolver->expects($this->exactly(2))
            ->method('setDefaults')
            ->withConsecutive([$parentDefaultOptions], [$defaultOptions])
            ->willReturnSelf();

        $this->getTestFormType()->configureOptions($resolver);
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptionsDataProvider(): array
    {
        return [
            [
                'parentDefaultOptions' => [
                    'field_type' => EntityType::class,
                    'field_options' => [],
                    'translatable'  => false,
                ],
            ],
        ];
    }

    /**
     * @dataProvider bindDataProvider
     */
    public function testBindData(
        array $bindData,
        array $formData,
        array $viewData,
        array $customOptions = []
    ) {
        // bind method should be tested in functional test
    }

    /**
     * {@inheritDoc}
     */
    public function bindDataProvider(): array
    {
        return [
            'defaultOptions' =>  [
                'bindData' => [],
                'formData' => [],
                'viewData' => [],
                'customOptions' => []
            ]
        ];
    }
}
