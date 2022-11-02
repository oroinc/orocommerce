<?php

namespace Oro\Bundle\RedirectBundle\DataProvider;

use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\Generator\CanonicalUrlGenerator;

class CanonicalDataProvider
{
    /**
     * @var CanonicalUrlGenerator
     */
    protected $canonicalUrlGenerator;

    public function __construct(CanonicalUrlGenerator $canonicalUrlGenerator)
    {
        $this->canonicalUrlGenerator = $canonicalUrlGenerator;
    }

    /**
     * @param SluggableInterface $data
     * @return string
     */
    public function getUrl(SluggableInterface $data)
    {
        return $this->canonicalUrlGenerator->getUrl($data);
    }
}
