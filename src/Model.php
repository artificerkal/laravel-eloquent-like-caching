<?php

namespace Artificerkal\LaravelEloquentLikeCaching;

use Artificerkal\LaravelEloquentLikeCaching\Structures\ArrayStruct;
use Artificerkal\LaravelEloquentLikeCaching\Structures\QueueStruct;
use Artificerkal\LaravelEloquentLikeCaching\Structures\StackStruct;
use Illuminate\Support\Collection;
use Illuminate\Support\Traits\ForwardsCalls;
use Jenssegers\Model\Model as BaseModel;

abstract class Model extends BaseModel
{
    use ForwardsCalls;


    /**
     * The attribute which will be used as the model key
     *
     * @var string
     */
    protected $keyName = 'key';

    /**
     * The model key prefix which is used for all models of this type. Defaults to the model class
     *
     * @var string
     */
    protected $keyPrefix;

    /**
     * The data structure that this group of models should be stored as
     *
     * @var string
     */
    protected $struct = 'array';

    protected $structMap = [
        'array' => ArrayStruct::class,
        // 'queue' => QueueStruct::class,
        // 'stack' => StackStruct::class,
    ];

    /**
     * Get the Connector that will manage this model being stored in a key-value storage system
     *
     * @return \Artificerkal\LaravelEloquentLikeCaching\Contracts\Connector
     */
    public function cacheStructure()
    {
        $structureClass = $this->structMap[$this->struct];
        return new $structureClass($this);
    }

    public function getKeyPrefix()
    {
        return ($this->keyPrefix ?? static::class) . ':';
    }

    public function getKeyName()
    {
        return $this->keyName;
    }

    public function getKey()
    {
        $keyName = $this->getKeyName();
        return $this->$keyName;
    }

    /**
     * Save this model
     *
     * @return bool
     */
    public function save()
    {
        return $this->cacheStructure()->save();
    }

    /**
     * Delete this model
     *
     * @return bool
     */
    public function delete()
    {
        return $this->cacheStructure()->delete();
    }

    /**
     * Destroy the models for the given keys.
     *
     * @param  \Illuminate\Support\Collection|array|int|string  $keys
     * @return int
     */
    public static function destroy($keys)
    {
        $count = 0;

        if ($keys instanceof Collection) {
            $keys = $keys->all();
        }

        $keys = is_array($keys) ? $keys : func_get_args();

        $instance = new static;

        foreach ($keys as $key) {
            if ($instance->find($key)->delete()) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Handle dynamic method calls into the model.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->forwardCallTo($this->cacheStructure(), $method, $parameters);
    }
}
