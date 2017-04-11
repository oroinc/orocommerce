<?php

namespace Oro\Bundle\DPDBundle\Handler;

use Oro\Bundle\CacheBundle\Action\Handler\InvalidateCacheActionHandlerInterface;
use Oro\Bundle\CacheBundle\DataStorage\DataStorageInterface;
use Oro\Bundle\DPDBundle\Cache\ZipCodeRulesCache;

class InvalidateCacheActionHandler implements InvalidateCacheActionHandlerInterface
{
    const PARAM_TRANSPORT_ID = 'transportId';

    /**
     * @var ZipCodeRulesCache
     */
    private $zipCodeRulesCache;

    /**
     * @param ZipCodeRulesCache $zipCodeRulesCache
     */
    public function __construct(ZipCodeRulesCache $zipCodeRulesCache)
    {
        $this->zipCodeRulesCache = $zipCodeRulesCache;
    }

    /**
     * @param DataStorageInterface $dataStorage
     */
    public function handle(DataStorageInterface $dataStorage)
    {
        $this->zipCodeRulesCache->deleteAll($dataStorage->get(self::PARAM_TRANSPORT_ID));
    }
}
