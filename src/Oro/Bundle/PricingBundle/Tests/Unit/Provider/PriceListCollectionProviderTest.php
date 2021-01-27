<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Provider\PriceListCollectionProvider;
use Oro\Bundle\PricingBundle\Provider\PriceListSequenceMember;
use Oro\Bundle\PricingBundle\SystemConfig\PriceListConfigConverter;
use Oro\Component\Testing\Unit\EntityTrait;

class PriceListCollectionProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $registry;

    /**
     * @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $configManager;

    /**
     * @var PriceListConfigConverter|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $configConverter;

    /**
     * @var PriceListCollectionProvider
     */
    protected $provider;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->configConverter = $this->createMock(PriceListConfigConverter::class);

        $this->provider = new PriceListCollectionProvider(
            $this->registry,
            $this->configManager,
            $this->configConverter
        );
    }

    /**
     * @dataProvider mergeCollectionDataProvider
     * @param array $collection
     * @param bool $expected
     */
    public function testContainMergeDisallowed($collection, $expected)
    {
        $this->assertSame($expected, $this->provider->containMergeDisallowed($collection));
    }

    /**
     * @return array
     */
    public function mergeCollectionDataProvider()
    {
        return [
            'does not contain' => [
                [
                    new PriceListSequenceMember(new PriceList(), true),
                    new PriceListSequenceMember(new PriceList(), true)
                ],
                false
            ],
            'contain' => [
                [
                    new PriceListSequenceMember(new PriceList(), true),
                    new PriceListSequenceMember(new PriceList(), false)
                ],
                true
            ],
        ];
    }

    /**
     * @dataProvider scheduledCollectionDataProvider
     * @param array $collection
     * @param bool $expected
     */
    public function testContainScheduled($collection, $expected)
    {
        $this->assertSame($expected, $this->provider->containScheduled($collection));
    }

    /**
     * @return array
     */
    public function scheduledCollectionDataProvider()
    {
        return [
            'does not contain' => [
                [
                    new PriceListSequenceMember($this->getEntity(PriceList::class, ['containSchedule' => false]), true),
                    new PriceListSequenceMember($this->getEntity(PriceList::class, ['containSchedule' => false]), true),
                ],
                false
            ],
            'contain' => [
                [
                    new PriceListSequenceMember($this->getEntity(PriceList::class, ['containSchedule' => false]), true),
                    new PriceListSequenceMember($this->getEntity(PriceList::class, ['containSchedule' => true]), true),
                ],
                true
            ],
        ];
    }
}
