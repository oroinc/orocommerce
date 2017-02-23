<?php

namespace Oro\Bundle\ProductBundle\Twig;

use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;

class ProductImageExtension extends \Twig_Extension
{
    const NAME = 'oro_product_image';

    /**
     * @var AttachmentManager
     */
    private $attachmentManager;

    /**
     * @param AttachmentManager $attachmentManager
     */
    public function __construct(AttachmentManager $attachmentManager)
    {
        $this->attachmentManager = $attachmentManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction(
                'filtered_product_image_url',
                [$this->attachmentManager, 'getFilteredImageUrl']
            )
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
