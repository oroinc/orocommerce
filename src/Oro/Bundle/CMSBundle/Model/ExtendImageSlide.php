<?php

namespace Oro\Bundle\CMSBundle\Model;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\CMSBundle\Entity\ImageSlide;

/**
 * Extend class which allow to make ImageSlide entity extandable.
 *
 * @method null|File getMainImage()
 * @method ImageSlide setMainImage(File $image)
 * @method null|File getMediumImage()
 * @method ImageSlide setMediumImage(File $image)
 * @method null|File getSmallImage()
 * @method ImageSlide setSmallImage(File $image)
 */
class ExtendImageSlide
{
    /**
     * Constructor
     *
     * The real implementation of this method is auto generated.
     *
     * IMPORTANT: If the derived class has own constructor it must call parent constructor.
     */
    public function __construct()
    {
    }
}
