<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Entity\Stub;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\CMSBundle\Entity\ImageSlide as BaseImageSlide;

class ImageSlide extends BaseImageSlide
{
    private ?File $extraLargeImage = null;
    private ?File $extraLargeImage2x = null;
    private ?File $extraLargeImage3x = null;
    private ?File $largeImage = null;
    private ?File $largeImage2x = null;
    private ?File $largeImage3x = null;
    private ?File $mediumImage = null;
    private ?File $mediumImage2x = null;
    private ?File $mediumImage3x = null;
    private ?File $smallImage = null;
    private ?File $smallImage2x = null;
    private ?File $smallImage3x = null;

    public function getExtraLargeImage(): ?File
    {
        return $this->extraLargeImage;
    }

    public function setExtraLargeImage(?File $extraLargeImage): self
    {
        $this->extraLargeImage = $extraLargeImage;

        return $this;
    }

    public function getExtraLargeImage2x(): ?File
    {
        return $this->extraLargeImage2x;
    }

    public function setExtraLargeImage2x(?File $extraLargeImage2x): self
    {
        $this->extraLargeImage2x = $extraLargeImage2x;

        return $this;
    }

    public function getExtraLargeImage3x(): ?File
    {
        return $this->extraLargeImage3x;
    }

    public function setExtraLargeImage3x(?File $extraLargeImage3x): self
    {
        $this->extraLargeImage3x = $extraLargeImage3x;

        return $this;
    }

    public function getLargeImage(): ?File
    {
        return $this->largeImage;
    }

    public function setLargeImage(File $largeImage): self
    {
        $this->largeImage = $largeImage;

        return $this;
    }

    public function getLargeImage2x(): ?File
    {
        return $this->largeImage2x;
    }

    public function setLargeImage2x(?File $largeImage2x): self
    {
        $this->largeImage2x = $largeImage2x;

        return $this;
    }

    public function getLargeImage3x(): ?File
    {
        return $this->largeImage3x;
    }

    public function setLargeImage3x(?File $largeImage3x): self
    {
        $this->largeImage3x = $largeImage3x;

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

    public function getMediumImage2x(): ?File
    {
        return $this->mediumImage2x;
    }

    public function setMediumImage2x(?File $mediumImage2x): self
    {
        $this->mediumImage2x = $mediumImage2x;

        return $this;
    }

    public function getMediumImage3x(): ?File
    {
        return $this->mediumImage3x;
    }

    public function setMediumImage3x(?File $mediumImage3x): self
    {
        $this->mediumImage3x = $mediumImage3x;

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

    public function getSmallImage2x(): ?File
    {
        return $this->smallImage2x;
    }

    public function setSmallImage2x(?File $smallImage2x): self
    {
        $this->smallImage2x = $smallImage2x;

        return $this;
    }

    public function getSmallImage3x(): ?File
    {
        return $this->smallImage3x;
    }

    public function setSmallImage3x(?File $smallImage3x): self
    {
        $this->smallImage3x = $smallImage3x;

        return $this;
    }
}
