<?php

namespace OroB2B\Component\Checkout\Entity;

interface SourceDocumentAwareInterface
{
    /**
     * @return object
     */
    public function getSourceDocument();
}
