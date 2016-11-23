<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Entity\Stub;

use Oro\Component\WebCatalog\Entity\ContentNodeInterface;

class ContentNodeStub implements ContentNodeInterface
{
    use MetaFieldSetterGetterTrait {
        MetaFieldSetterGetterTrait::__construct as private traitConstructor;
    }

    public function __construct()
    {
        $this->traitConstructor();
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 1;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'name';
    }
}
