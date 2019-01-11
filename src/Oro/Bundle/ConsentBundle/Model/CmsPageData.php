<?php

namespace Oro\Bundle\ConsentBundle\Model;

/**
 * This object is used on the view and in layoutProvider
 * as a resource of resolved data from Consent and ConsentAcceptance objects
 */
class CmsPageData implements \JsonSerializable
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $url;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string $url
     *
     * @return $this
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'id'  => $this->getId(),
            'url' => $this->getUrl()
        ];
    }
}
