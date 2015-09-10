<?php

namespace OroB2B\Bundle\CatalogBundle\Handler;

use Symfony\Component\HttpFoundation\Request;

class RequestProductHandler
{
    const CATEGORY_ID_KEY = 'categoryId';

    /** @var  Request|null */
    protected $request;

    public function setRequest($request)
    {
        $this->request = $request;
    }

    /**
     * @return bool|integer
     */
    public function getCategoryId()
    {
        if (!$this->request) {
            return false;
        }

        $value = $this->request->get(self::CATEGORY_ID_KEY);

        return $value ? (int)$value : false;
    }
}
