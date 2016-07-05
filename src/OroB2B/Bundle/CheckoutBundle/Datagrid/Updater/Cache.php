<?php

namespace OroB2B\Bundle\CheckoutBundle\Datagrid\Updater;

use Doctrine\Common\Cache\Cache as DoctrineCache;

class Cache
{
    /**
     * @var DoctrineCache
     */
    protected $cache;

    /**
     * @param DoctrineCache $cache
     */
    public function __construct(DoctrineCache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @param string   $key
     * @param \Closure $fullReadCallback
     * @return mixed
     */
    public function read($key, $fullReadCallback)
    {
        if (!is_callable($fullReadCallback)) {
            throw new \InvalidArgumentException('Read through callback has to be provided');
        }
        
        if (!$key || !is_string($key)) {
            throw new \InvalidArgumentException('String key expected');
        }

        if ($this->cache->contains($key)) {
            return $this->cache->fetch($key);
        }
    
        $updates = $fullReadCallback();

        if ($this->cache) {
            $this->cache->save($key, $updates);
        }
        
        return $updates;
    }
}
