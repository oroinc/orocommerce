<?php

namespace OroB2B\Bundle\ProductBundle\Storage;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

class ProductDataStorage
{
    const PRODUCT_DATA_KEY = 'orob2b_product_data';
    const STORAGE_KEY = 'storage';

    const ENTITY_DATA_KEY = 'entity_data';
    const ENTITY_ITEMS_DATA_KEY = 'entity_items_data';

    const PRODUCT_SKU_KEY = 'productSku';
    const PRODUCT_QUANTITY_KEY = 'productQuantity';

    /** @var SessionInterface */
    protected $session;

    /**
     * @param SessionInterface $session
     */
    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * @param array $data
     */
    public function set(array $data)
    {
        $this->session->set(self::PRODUCT_DATA_KEY, serialize($data));
    }

    /**
     * @return array
     */
    public function get()
    {
        $data = @unserialize($this->session->get(self::PRODUCT_DATA_KEY, null));

        return $data !== false && is_array($data) ? $data : [];
    }

    public function remove()
    {
        $this->session->remove(self::PRODUCT_DATA_KEY);
    }
}
