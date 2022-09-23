<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Entity\Stub;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\CMSBundle\Entity\ContentTemplate as BaseContentTemplate;

class ContentTemplateStub extends BaseContentTemplate
{
    private ?File $previewImage = null;

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getPreviewImage(): ?File
    {
        return $this->previewImage;
    }

    public function setPreviewImage(File $previewImage): self
    {
        $this->previewImage = $previewImage;

        return $this;
    }
}
