<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Provider;

use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Provider\CouponEntityNameProvider;
use PHPUnit\Framework\TestCase;

class CouponEntityNameProviderTest extends TestCase
{
    /**
     * @var CouponEntityNameProvider
     */
    private $provider;

    protected function setUp(): void
    {
        $this->provider = new CouponEntityNameProvider();
    }

    /**
     * @dataProvider nameDataProvider
     * @param string $format
     * @param object $entity
     * @param string $expected
     */
    public function testGetName($format, $entity, $expected)
    {
        $this->assertEquals($expected, $this->provider->getName($format, 'en', $entity));
    }

    /**
     * @return \Generator
     */
    public function nameDataProvider(): ?\Generator
    {
        foreach ($this->getFormats() as $format) {
            yield [$format, new \stdClass(), false];
            yield [$format, (new Coupon())->setCode('MyCode'), 'MyCode'];
        }
    }

    /**
     * @dataProvider dqlNameDataProvider
     * @param string $format
     * @param string $entityClass
     * @param string $expected
     */
    public function testGetNameDQL($format, $entityClass, $expected)
    {
        $this->assertEquals($expected, $this->provider->getNameDQL($format, 'en', $entityClass, 'alias'));
    }

    /**
     * @return \Generator
     */
    public function dqlNameDataProvider(): ?\Generator
    {
        foreach ($this->getFormats() as $format) {
            yield [$format, \stdClass::class, false];
            yield [$format, Coupon::class, 'alias.code'];
        }
    }

    private function getFormats(): array
    {
        return [
            CouponEntityNameProvider::FULL,
            CouponEntityNameProvider::SHORT
        ];
    }
}
