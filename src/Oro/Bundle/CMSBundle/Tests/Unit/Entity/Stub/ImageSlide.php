<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Entity\Stub;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\CMSBundle\Entity\ImageSlide as BaseImageSlide;

class ImageSlide extends BaseImageSlide
{
    /** @var File */
    private $mainImage;

    /** @var File */
    private $mediumImage;

    /** @var File */
    private $smallImage;

    /**
     * @return null|File
     */
    public function getMainImage(): ?File
    {
        return $this->mainImage;
    }

    /**
     * @param File $mainImage
     * @return $this
     */
    public function setMainImage(File $mainImage): self
    {
        $this->mainImage = $mainImage;

        return $this;
    }

    /**
     * @return null|File
     */
    public function getMediumImage(): ?File
    {
        return $this->mediumImage;
    }

    /**
     * @param File $mediumImage
     * @return $this
     */
    public function setMediumImage(File $mediumImage): self
    {
        $this->mediumImage = $mediumImage;

        return $this;
    }

    /**
     * @return null|File
     */
    public function getSmallImage(): ?File
    {
        return $this->smallImage;
    }

    /**
     * @param File $smallImage
     * @return $this
     */
    public function setSmallImage(File $smallImage): self
    {
        $this->smallImage = $smallImage;

        return $this;
    }
}
