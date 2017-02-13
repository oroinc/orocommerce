<?php

namespace Oro\Bundle\SEOBundle\Model;

interface WebsiteInterface
{
    /**
     * @return int
     */
    public function getId();

    /**
     * @return string
     */
    public function getName();

    /**
     * @return bool
     */
    public function isDefault();
}
