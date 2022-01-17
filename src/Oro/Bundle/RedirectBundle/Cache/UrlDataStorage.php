<?php

namespace Oro\Bundle\RedirectBundle\Cache;

/**
 * Stores localized slugs and URLs
 */
class UrlDataStorage implements \JsonSerializable
{
    const PREFIX_KEY = 'p';

    /**
     * @var array
     */
    private $data = [];

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
        if ($slug) {
            $url = \substr($url, 0, -\strlen($slug));
            $url = \rtrim($url, '/');
        }

        $localizationId = (int)$localizationId;
        $this->data[self::getUrlKey($routeParameters)][$localizationId] = [
            self::PREFIX_KEY => $url ?: null,
            UrlCacheInterface::SLUG_KEY => $slug
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
     * @return bool|string
     */
    public function getUrl($routeParameters, $localizationId = null)
    {
        if ($data = $this->getLocalizedData($routeParameters, $localizationId)) {
            if (array_key_exists(self::PREFIX_KEY, $data)) {
                $url = $data[self::PREFIX_KEY];
                if (!empty($data[UrlCacheInterface::SLUG_KEY])) {
                    $url .= '/' . $data[UrlCacheInterface::SLUG_KEY];
                }

                return $url;
            }

            return $data[UrlCacheInterface::URL_KEY];
        }

        return false;
    }

    /**
     * @param mixed $routeParameters
     * @param null|int $localizationId
     * @return bool|string
     */
    public function getSlug($routeParameters, $localizationId = null)
    {
        if ($data = $this->getLocalizedData($routeParameters, $localizationId)) {
            return $data[UrlCacheInterface::SLUG_KEY];
        }

        return false;
    }

    /**
     * @param array $routeParameters
     * @return string
     */
    public static function getUrlKey($routeParameters = [])
    {
        return md5(serialize($routeParameters));
    }

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
            } elseif (array_key_exists(UrlCacheInterface::URL_KEY, $this->data)) {
                // Compatibility with older versions of cache
                return $this->data;
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        return $this->data;
    }
}
