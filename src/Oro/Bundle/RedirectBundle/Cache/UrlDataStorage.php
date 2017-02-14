<?php

namespace Oro\Bundle\RedirectBundle\Cache;

class UrlDataStorage
{
    const SLUG_KEY = 's';
    const URL_KEY = 'u';

    /**
     * @var array
     */
    private $data = [];

    /**
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * @param array $data
     * @return UrlDataStorage
     */
    public static function __set_state($data)
    {
        if (is_array($data) && array_key_exists('data', $data) && is_array($data['data'])) {
            return new self($data['data']);
        }

        return new self();
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param mixed $routeParameters
     * @param string $url
     * @param string|null $slug
     */
    public function setUrl($routeParameters, $url, $slug = null)
    {
        $this->data[self::getUrlKey($routeParameters)] = [self::URL_KEY => $url, self::SLUG_KEY => $slug];
    }

    /**
     * @param mixed $routeParameters
     */
    public function removeUrl($routeParameters)
    {
        unset($this->data[self::getUrlKey($routeParameters)]);
    }

    /**
     * @param mixed $routeParameters
     * @return string|null
     */
    public function getUrl($routeParameters)
    {
        $key = self::getUrlKey($routeParameters);
        if (array_key_exists($key, $this->data)) {
            return $this->data[$key][self::URL_KEY];
        }

        return null;
    }

    public function getSlug($routeParameters)
    {
        $key = self::getUrlKey($routeParameters);
        if (array_key_exists($key, $this->data)) {
            return $this->data[$key][self::SLUG_KEY];
        }

        return null;
    }

    /**
     * @param array $routeParameters
     * @return string
     */
    public static function getUrlKey($routeParameters = [])
    {
        return md5(serialize($routeParameters));
    }

    /**
     * @param UrlDataStorage $storage
     */
    public function merge(UrlDataStorage $storage)
    {
        $this->data = array_merge($this->data, $storage->getData());
    }
}
