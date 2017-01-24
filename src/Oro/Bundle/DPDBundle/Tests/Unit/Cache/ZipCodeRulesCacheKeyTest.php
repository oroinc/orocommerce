<?php

namespace Oro\Bundle\DPDBundle\Tests\Unit\Cache;

use Oro\Bundle\DPDBundle\Cache\ZipCodeRulesCacheKey;
use Oro\Bundle\DPDBundle\Entity\DPDTransport;
use Oro\Bundle\DPDBundle\Model\ZipCodeRulesRequest;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Component\Testing\Unit\EntityTrait;

class ZipCodeRulesCacheKeyTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        self::assertPropertyAccessors(new ZipCodeRulesCacheKey(), [
            ['transport', $this->getEntity(DPDTransport::class, ['id' => 1])],
            ['zipCodeRulesRequest', new ZipCodeRulesRequest()],
            ['methodId', 'method'],
        ]);
    }

    public function testGenerateKey()
    {
        $key1 = new ZipCodeRulesCacheKey();
        $key2 = new ZipCodeRulesCacheKey();
        $request1 = new ZipCodeRulesRequest();
        $key1->setZipCodeRulesRequest($request1);
        $request2 = new ZipCodeRulesRequest();
        $key2->setZipCodeRulesRequest($request2);

        $this->assertKeysEquals($key1, $key2);

        $key1->setMethodId('method1');
        $this->assertKeysNotEquals($key1, $key2);
        $key2->setMethodId('method2');
        $this->assertKeysNotEquals($key1, $key2);
        $key2->setMethodId('method1');
        $this->assertKeysEquals($key1, $key2);

        $key1->setTransport($this->getEntity(DPDTransport::class, ['id' => 1]));
        $this->assertKeysNotEquals($key1, $key2);
        $key2->setTransport($this->getEntity(DPDTransport::class, ['id' => 2]));
        $this->assertKeysNotEquals($key1, $key2);
        $key2->setTransport($this->getEntity(DPDTransport::class, ['id' => 1]));
        $this->assertKeysEquals($key1, $key2);
    }

    /**
     * @param ZipCodeRulesCacheKey $key1
     * @param ZipCodeRulesCacheKey $key2
     */
    protected function assertKeysEquals(ZipCodeRulesCacheKey $key1, ZipCodeRulesCacheKey $key2)
    {
        $this->assertEquals($key1->generateKey(), $key2->generateKey());
    }

    /**
     * @param ZipCodeRulesCacheKey $key1
     * @param ZipCodeRulesCacheKey $key2
     */
    protected function assertKeysNotEquals(ZipCodeRulesCacheKey $key1, ZipCodeRulesCacheKey $key2)
    {
        $this->assertNotEquals($key1->generateKey(), $key2->generateKey());
    }
}
