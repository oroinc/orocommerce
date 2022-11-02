<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Entity\Stub;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\CMSBundle\Entity\ImageSlide as BaseImageSlide;

class ImageSlide extends BaseImageSlide
{
    private ?File $mainImage = null;

    private ?File $mediumImage = null;

    private ?File $smallImage = null;

    public function getMainImage(): ?File
    {
        return $this->mainImage;
    }

    public function setMainImage(File $mainImage): self
    {
        $this->mainImage = $mainImage;

        return $this;
    }

    public function getMediumImage(): ?File
    {
        return $this->mediumImage;
    }

    public function setMediumImage(?File $mediumImage): self
    {
        $this->mediumImage = $mediumImage;

        return $this;
    }

    public function getSmallImage(): ?File
    {
        return $this->smallImage;
    }

    public function setSmallImage(?File $smallImage): self
    {
        $this->smallImage = $smallImage;

        return $this;
    }
}
