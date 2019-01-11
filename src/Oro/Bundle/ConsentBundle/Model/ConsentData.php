<?php

namespace Oro\Bundle\ConsentBundle\Model;

use Oro\Bundle\ConsentBundle\Entity\Consent;

/**
 * This object is used on the view and in layoutProvider
 * as a resource of resolved data from Consent and ConsentAcceptance objects
 */
class ConsentData implements \JsonSerializable
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $title;

    /**
     * @var boolean
     */
    private $required;

    /**
     * @var CmsPageData
     */
    private $cmsPageData = null;

    /**
     * @var boolean
     */
    private $accepted = false;

    /**
     ** @param Consent $consent
     */
    public function __construct(Consent $consent)
    {
        $this->id = $consent->getId();
        $this->required = $consent->isMandatory();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $localizedTitle
     *
     * @return $this
     */
    public function setTitle($localizedTitle)
    {
        $this->title = $localizedTitle;

        return $this;
    }

    /**
     * @return bool
     */
    public function isRequired()
    {
        return $this->required;
    }

    /**
     * @return boolean
     */
    public function isAccepted()
    {
        return $this->accepted;
    }

    /**
     * @param bool $isAccepted
     *
     * @return $this
     */
    public function setAccepted($isAccepted)
    {
        $this->accepted = $isAccepted;

        return $this;
    }

    /**
     * @return null|CmsPageData
     */
    public function getCmsPageData()
    {
        return $this->cmsPageData;
    }

    /**
     * @param CmsPageData $cmsPage
     *
     * @return $this
     */
    public function setCmsPageData(CmsPageData $cmsPage)
    {
        $this->cmsPageData = $cmsPage;

        return $this;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        $consentDataArray = [
            'consentId' => $this->getId(),
            'required' => $this->isRequired(),
            'consentTitle' => $this->getTitle(),
            'accepted' => $this->isAccepted()
        ];

        if (null !== $this->cmsPageData) {
            $consentDataArray['cmsPageData'] = $this->cmsPageData->jsonSerialize();
        }

        return $consentDataArray;
    }
}
