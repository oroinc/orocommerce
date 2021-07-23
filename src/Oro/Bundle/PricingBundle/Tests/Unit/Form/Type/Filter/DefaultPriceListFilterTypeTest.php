<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Form\Type\Filter;

use Oro\Bundle\FilterBundle\Form\Type\Filter\ChoiceFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\EntityFilterType;
use Oro\Bundle\FilterBundle\Tests\Unit\Form\Type\AbstractTypeTestCase;
use Oro\Bundle\PricingBundle\Form\Type\Filter\DefaultPriceListFilterType;
use Oro\Bundle\PricingBundle\Provider\PriceListProvider;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class DefaultPriceListFilterTypeTest extends AbstractTypeTestCase
{
    /**
     * @var PriceListProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $provider;

    /**
     * @var ShardManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $shardManager;

    /**
     * @var string
     */
    protected $priceListClass;

    /**
     * @var DefaultPriceListFilterType
     */
    private $type;

    protected function setUp(): void
    {
        $translator = $this->createMockTranslator();
        $this->shardManager = $this->createMock(ShardManager::class);
        $this->provider = $this->createMock(PriceListProvider::class);
        $this->priceListClass = 'Oro\Bundle\PricingBundle\Entity\PriceList';
        $this->type = new DefaultPriceListFilterType(
            $translator,
            $this->provider,
            $this->shardManager,
            $this->priceListClass
        );

        parent::setUp();
    }

    /**
     * @return EntityFilterType
     */
    protected function getTestFormType()
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
    public function testConfigureOptions(array $parentDefaultOptions, array $requiredOptions = array())
    {
        $defaultOptions = [
            'field_options' => [
                'class' => $this->priceListClass,
                'choice_label' => 'name'
            ],
            'required' => true,
        ];

        $this->shardManager
            ->expects($this->once())
            ->method('isShardingEnabled')
            ->willReturn(true);

        $resolver = $this->createMockOptionsResolver();
        $resolver
            ->expects($this->exactly(2))
            ->method('setDefaults')
            ->withConsecutive([$parentDefaultOptions], [$defaultOptions])
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

        $this->shardManager
            ->expects($this->once())
            ->method('isShardingEnabled')
            ->willReturn(false);

        $resolver = $this->createMockOptionsResolver();

        $resolver
            ->expects($this->exactly(2))
            ->method('setDefaults')
            ->withConsecutive([$parentDefaultOptions], [$defaultOptions])
            ->willReturnSelf();

        $this->getTestFormType()->configureOptions($resolver);
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptionsDataProvider()
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
        array $customOptions = array()
    ) {
        // bind method should be tested in functional test
    }

    /**
     * {@inheritDoc}
     */
    public function bindDataProvider()
    {
        return array(
            'empty' => array(
                'bindData' => array(),
                'formData' => array(),
                'viewData' => array(),
            ),
        );
    }
}
