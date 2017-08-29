<?php

namespace Oro\Bundle\RedirectBundle\Cache;

class UrlDataStorage
{
    const SLUG_KEY = 's';
    const URL_KEY = 'u';
    const DEFAULT_LOCALIZATION_ID = 0;

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
     * @param null|int $localizationId
     */
    public function setUrl($routeParameters, $url, $slug = null, $localizationId = null)
    {
        $localizationId = (int)$localizationId;
        $this->data[self::getUrlKey($routeParameters)][$localizationId] = [
            self::URL_KEY => $url,
            self::SLUG_KEY => $slug
        ];
    }

    /**
     * @param mixed $routeParameters
     * @param null|int $localizationId
     */
    public function removeUrl($routeParameters, $localizationId = null)
    {
        $localizationId = (int)$localizationId;
        $urlKey = self::getUrlKey($routeParameters);
        unset($this->data[$urlKey][$localizationId]);

        if (empty($this->data[$urlKey])) {
            unset($this->data[$urlKey]);
        }
    }

    /**
     * @param mixed $routeParameters
     * @param null|int $localizationId
     * @return null|string
     */
    public function getUrl($routeParameters, $localizationId = null)
    {
        if ($data = $this->getLocalizedData($routeParameters, $localizationId)) {
            return $data[self::URL_KEY];
        }

        return null;
    }

    /**
     * @param mixed $routeParameters
     * @param null|int $localizationId
     * @return null|string
     */
    public function getSlug($routeParameters, $localizationId = null)
    {
        if ($data = $this->getLocalizedData($routeParameters, $localizationId)) {
            return $data[self::SLUG_KEY];
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
        $this->data = array_replace_recursive($this->data, $storage->getData());
    }

    /**
     * @param mixed $routeParameters
     * @param null|int $localizationId
     * @return null|array
     */
    private function getLocalizedData($routeParameters, $localizationId = null)
    {
        $key = self::getUrlKey($routeParameters);
        $localizationId = (int)$localizationId;
        if (array_key_exists($key, $this->data)) {
            if (array_key_exists($localizationId, $this->data[$key])) { // current localization
                return $this->data[$key][$localizationId];
            } elseif (array_key_exists(self::DEFAULT_LOCALIZATION_ID, $this->data[$key])) { // default localization
                return $this->data[$key][self::DEFAULT_LOCALIZATION_ID];
            } elseif (array_key_exists(self::URL_KEY, $this->data)) { // Compatibility with older versions of cache
                return $this->data;
            }
        }

        return null;
    }
}
