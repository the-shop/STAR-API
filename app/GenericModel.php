<?php

namespace App;

/**
 * Class GenericModel
 * @package App
 */
class GenericModel extends StarModel
{
    /**
     * The collection associated with the model.
     *
     * @var string
     */
    protected $collection;

    /**
     * Collection name that's set statically to be reused on each instance creation
     *
     * @var string
     */
    protected static $collectionName;

    /**
     * Get the guarded attributes for the model.
     *
     * @return array
     */
    public function getGuarded()
    {
        return $this->guarded = [];
    }

    /**
     * Custom constructor so that we can set correct collection for each created instance
     *
     * GenericModel constructor.
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->collection = self::$collectionName;
    }

    /**
     * @param $resourceName
     * @return $this
     */
    public static function setCollection($resourceName)
    {
        self::$collectionName = $resourceName;
    }
}
