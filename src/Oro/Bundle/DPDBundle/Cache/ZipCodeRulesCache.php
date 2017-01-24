<?php

namespace Oro\Bundle\DPDBundle\Cache;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\DPDBundle\Entity\DPDTransport;
use Oro\Bundle\DPDBundle\Model\ZipCodeRulesRequest;
use Oro\Bundle\DPDBundle\Model\ZipCodeRulesResponse;

class ZipCodeRulesCache
{
    /**
     * 24 hours, 60 * 60 * 24.
     */
    const LIFETIME = 86400;

    const NAME_SPACE = 'oro_dpd_zip_code_rules';

    /**
     * @var CacheProvider
     */
    protected $cache;

    /**
     * @param CacheProvider $cache
     */
    public function __construct(CacheProvider $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @param ZipCodeRulesCacheKey $key
     *
     * @return bool
     */
    public function containsZipCodeRules(ZipCodeRulesCacheKey $key)
    {
        return $this->containsZipCodeRulesByStringKey($this->generateStringKey($key));
    }

    /**
     * @param string $stringKey
     *
     * @return bool
     */
    protected function containsZipCodeRulesByStringKey($stringKey)
    {
        return $this->cache->contains($stringKey);
    }

    /**
     * @param ZipCodeRulesCacheKey $key
     *
     * @return bool|ZipCodeRulesResponse
     */
    public function fetchZipCodeRules(ZipCodeRulesCacheKey $key)
    {
        $stringKey = $this->generateStringKey($key);
        if (!$this->containsZipCodeRulesByStringKey($stringKey)) {
            return false;
        }

        return $this->cache->fetch($stringKey);
    }

    /**
     * @param ZipCodeRulesCacheKey $key
     * @param ZipCodeRulesResponse $zipCodeRules
     *
     * @return $this
     */
    public function saveZipCodeRules(ZipCodeRulesCacheKey $key, ZipCodeRulesResponse $zipCodeRules)
    {
        $interval = 0;
        $invalidateCacheAt = $key->getTransport()->getInvalidateCacheAt();
        if ($invalidateCacheAt) {
            $interval = $invalidateCacheAt->getTimestamp() - time();
        }
        if ($interval <= 0) {
            $interval = static::LIFETIME;
        }
        $this->cache->save($this->generateStringKey($key), $zipCodeRules, $interval);

        return $this;
    }

    /**
     * @param int $transportId
     */
    public function deleteAll($transportId)
    {
        $this->setNamespace($transportId);
        $this->cache->deleteAll();
    }

    /**
     * @param DPDTransport        $transport
     * @param ZipCodeRulesRequest $zipCodeRulesRequest
     * @param string              $methodId
     *
     * @return ZipCodeRulesCacheKey
     */
    public function createKey(
        DPDTransport $transport,
        ZipCodeRulesRequest $zipCodeRulesRequest,
        $methodId
    ) {
        return (new ZipCodeRulesCacheKey())->setTransport($transport)
            ->setZipCodeRulesRequest($zipCodeRulesRequest)
            ->setMethodId($methodId);
    }

    /**
     * @param ZipCodeRulesCacheKey $key
     *
     * @return string
     */
    protected function generateStringKey(ZipCodeRulesCacheKey $key)
    {
        $this->setNamespace($key->getTransport()->getId());
        $invalidateAt = '';
        if ($key->getTransport() && $key->getTransport()->getInvalidateCacheAt()) {
            $invalidateAt = $key->getTransport()->getInvalidateCacheAt()->getTimestamp();
        }

        return implode('_', [
            $key->generateKey(),
            $invalidateAt,
        ]);
    }

    /**
     * @param int $id
     */
    protected function setNamespace($id)
    {
        $this->cache->setNamespace(self::NAME_SPACE.'_'.$id);
    }
}
