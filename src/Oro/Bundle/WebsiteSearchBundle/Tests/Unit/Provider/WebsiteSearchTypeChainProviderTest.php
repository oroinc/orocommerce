<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Provider;

use Oro\Bundle\WebsiteSearchBundle\Provider\WebsiteSearchTypeChainProvider;
use Oro\Bundle\WebsiteSearchBundle\Provider\WebsiteSearchTypeInterface;

class WebsiteSearchTypeChainProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var WebsiteSearchTypeChainProvider */
    protected $provider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->provider = new WebsiteSearchTypeChainProvider();
    }

    public function testDefaultSearchType(): void
    {
        $type = $this->createMock(WebsiteSearchTypeInterface::class);

        $this->provider->setDefaultSearchType($type);

        $this->assertEquals($type, $this->provider->getDefaultSearchType());
    }

    /**
     * @param array $types
     * @param array $expects
     *
     * @dataProvider AddSearchTypeDataProvider
     */
    public function testAddSearchType(array $types, array $expects): void
    {
        foreach ($types as $type) {
            $this->provider->addSearchType($type['type'], $type['value']);
        }

        foreach ($expects as $type) {
            $this->assertEquals(
                $type['value'],
                $this->provider->getSearchType($type['type'])
            );
        }
    }

    /**
     * @return array
     */
    public function addSearchTypeDataProvider(): array
    {
        $type1 = $this->createMock(WebsiteSearchTypeInterface::class);
        $type2 = $this->createMock(WebsiteSearchTypeInterface::class);
        $type3 = $this->createMock(WebsiteSearchTypeInterface::class);

        return [
            'check types'                => [
                'types'   => [
                    ['type' => 'type_1', 'value' => $type1],
                    ['type' => 'type_2', 'value' => $type2],
                    ['type' => 'type_3', 'value' => $type3],
                ],
                'expects' => [
                    ['type' => 'type_1', 'value' => $type1],
                    ['type' => 'type_2', 'value' => $type2],
                    ['type' => 'type_3', 'value' => $type3],
                ],
            ],
            'check types with same type' => [
                'types'   => [
                    ['type' => 'type_1', 'value' => $type1],
                    ['type' => 'type_2', 'value' => $type2],
                    ['type' => 'type_2', 'value' => $type3],
                ],
                'expects' => [
                    ['type' => 'type_1', 'value' => $type1],
                    ['type' => 'type_2', 'value' => $type2],
                ],
            ],
        ];
    }

    /**
     * @param array $types
     * @param mixed $defaultType
     * @param array $expects
     *
     * @dataProvider getSearchTypeOrDefaultDataProvider
     */
    public function testGetSearchTypeOrDefault(array $types, $defaultType, array $expects): void
    {
        foreach ($types as $type) {
            $this->provider->addSearchType($type['type'], $type['value']);
        }

        $this->provider->setDefaultSearchType($defaultType);

        foreach ($expects as $type) {
            $this->assertEquals(
                $type['value'],
                $this->provider->getSearchTypeOrDefault($type['type'])
            );
        }
    }

    /**
     * @return array
     */
    public function getSearchTypeOrDefaultDataProvider(): array
    {
        $type1 = $this->createMock(WebsiteSearchTypeInterface::class);
        $type2 = $this->createMock(WebsiteSearchTypeInterface::class);
        $type3 = $this->createMock(WebsiteSearchTypeInterface::class);

        return [
            'check types with default value' => [
                'types'       => [
                    ['type' => 'type_1', 'value' => $type1],
                    ['type' => 'type_2', 'value' => $type2],
                    ['type' => 'type_3', 'value' => $type2],
                ],
                'defaultType' => $type3,
                'expects'     => [
                    ['type' => 'type_1', 'value' => $type1],
                    ['type' => 'type_2', 'value' => $type2],
                    ['type' => 'random_string', 'value' => $type3],
                    ['type' => '', 'value' => $type3],
                    ['type' => 'type_22', 'value' => $type3],
                ],
            ],
        ];
    }

    /**
     * @param array $types
     * @param array $expects
     *
     * @dataProvider getSearchTypesDataProvider
     */
    public function testAddSearchTypes(array $types, array $expects): void
    {
        foreach ($types as $type) {
            $this->provider->addSearchType($type['type'], $type['value']);
        }

        $this->assertEquals(
            $expects,
            $this->provider->getSearchTypes()
        );
    }

    /**
     * @return array
     */
    public function getSearchTypesDataProvider(): array
    {
        $type1 = $this->createMock(WebsiteSearchTypeInterface::class);
        $type2 = $this->createMock(WebsiteSearchTypeInterface::class);
        $type3 = $this->createMock(WebsiteSearchTypeInterface::class);

        return [
            'check types with default value' => [
                'types'   => [
                    ['type' => 'type_1', 'value' => $type1],
                    ['type' => 'type_2', 'value' => $type2],
                    ['type' => 'type_3', 'value' => $type2],
                ],
                'expects' => [
                    'type_1' => $type1,
                    'type_2' => $type2,
                    'type_3' => $type3,
                ],
            ],
        ];
    }
}
