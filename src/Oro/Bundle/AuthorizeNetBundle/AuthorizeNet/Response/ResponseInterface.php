<?php

namespace Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Response;

interface ResponseInterface
{
    /**
     * @return bool
     */
    public function isSuccessful();

    /**
     * @return string|null
     */
    public function getReference();

    /**
     * @return string|null
     */
    public function getMessage();
    
    /**
     * @return mixed
     */
    public function getData();
}
