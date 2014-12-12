<?php
namespace Lysine\DataMapper;

/**
 * Data数据逻辑
 */
abstract class Data {
    /**
     * 此Data class指定使用的Mapper class
     * @var string
     */
    static protected $mapper = '\Lysine\DataMapper\Mapper';

    /**
     * 存储服务名
     * @var string
     */
    static protected $service;

    /**
     * 存储集合名
     * @var string
     */
    static protected $collection;

    /**
     * 属性定义
     * @var array
     */
    static protected $attributes = array();

    /**
     * 是否只读
     * @var boolean
     */
    static protected $readonly = false;

    /**
     * 是否所有属性默认开启严格模式
     * @var
     */
    static protected $strict = false;

    /**
     * 是否新对象，还没有保存到存储服务内的
     * @var boolean
     */
    protected $fresh;

    /**
     * 数据内容
     * @var array
     */
    protected $values = array();

    /**
     * 被修改过的属性
     * @var array
     */
    protected $dirty = array();

    public function __before_save() {}
    public function __after_save() {}

    public function __before_insert() {}
    public function __after_insert() {}

    public function __before_update() {}
    public function __after_update() {}

    public function __before_delete() {}
    public function __after_delete() {}

    /**
     * @param array [$values]
     * @param array [$options]
     */
    public function __construct(array $values = null, array $options = null) {
        $defaults = array('fresh' => true);
        $options = $options ? array_merge($defaults, $options) : $defaults;

        $attributes = static::getMapper()->getAttributes();

        $this->fresh = $options['fresh'];

        if ($values) {
            foreach ($values as $key => $value) {
                if (isset($attributes[$key])) {
                    $this->set($key, $value, array('strict' => true, 'force' => true));
                }
            }
        }

        if ($this->isFresh()) {
            foreach ($attributes as $key => $attribute) {
                if (array_key_exists($key, $this->values)) {
                    continue;
                }

                $default = Types::factory($attribute['type'])->getDefaultValue($attribute);
                if ($default !== null) {
                    $this->change($key, $default);
                }
            }
        } else {
            $this->dirty = array();
        }
    }

    /**
     * 读取属性
     *
     * @magic
     * @param string $key
     * @return mixed
     */
    public function __get($key) {
        return $this->get($key);
    }

    /**
     * 修改属性
     *
     * @magic
     * @param string $key
     * $param mixed $value
     * @return void
     */
    public function __set($key, $value) {
        $this->set($key, $value, array('strict' => true));
    }

    /**
     * 检查属性值是否存在
     *
     * @magic
     * @param string $key
     * @return boolean
     */
    public function __isset($key) {
        return isset($this->values[$key]);
    }

    /**
     * 把数据打包到Data实例内
     * 这个方法不应该被直接调用，只提供给Mapper调用
     *
     * @internal
     * @param array $values
     * @param boolean $replace
     * @return $this
     */
    final public function __pack(array $values, $replace) {
        $this->values = $replace ? $values : array_merge($this->values, $values);
        $this->dirty = array();
        $this->fresh = false;

        return $this;
    }

    /**
     * 是否定义了指定属性
     *
     * @param string $key
     * @return boolean
     */
    public function has($key) {
        $mapper = static::getMapper();
        return (bool)$mapper->hasAttribute($key);
    }

    /**
     * 修改属性值
     *
     * @param string $key 属性名
     * @param mixed $value 属性值
     * @param array [$options]
     * @param boolean [$options:force=false] 强制修改，忽略refuse_update设置
     * @param boolean [$options:strict=true] 严格模式，出现错误会抛出异常，属性如果被标记为"strict"，就只能在严格模式下才能修改
     * @return $this
     *
     * @throws \UnexpectedValueException 如果属性未定义
     * @throws \UnexpectedValueException 把null赋值给一个不允许为null的属性
     * @throws \UnexpectedValueException 值没有通过设定的正则表达式检查
     * @throws \RuntimeException 属性被标记为“废弃”
     * @throws \RuntimeException 属性不允许更新修改
     */
    public function set($key, $value, array $options = null) {
        $defaults = array('force' => false, 'strict' => true);
        $options = $options ? array_merge($defaults, $options) : $defaults;

        $attribute = static::getMapper()->getAttribute($key);

        if (!$attribute) {
            if ($options['strict']) {
                throw new \UnexpectedValueException(get_class() .": Undefined property {$key}");
            }

            return $this;
        }

        if ($attribute['deprecated']) {
            if ($options['strict']) {
                throw new \RuntimeException(get_class() .": Property {$key} is deprecated");
            }

            return $this;
        }

        if ($attribute['strict'] && !$options['strict']) {
            return $this;
        }

        if (!$options['force'] && $attribute['refuse_update'] && !$this->isFresh()) {
            if ($options['strict']) {
                throw new \RuntimeException(get_class() .": Property {$key} refuse update");
            }

            return $this;
        }

        if ($value === '') {
            $value = null;
        }

        if ($value === null) {
            if (!$attribute['allow_null']) {
                throw new \UnexpectedValueException(get_class() .": Property {$key} not allow null");
            }
        } else {
            $value = $this->normalize($key, $value, $attribute);

            if ($attribute['pattern'] && !preg_match($attribute['pattern'], $value)) {
                throw new \UnexpectedValueException(get_class() .": Property {$key} mismatching pattern {$attribute['pattern']}");
            }
        }

        if (array_key_exists($key, $this->values)) {
            if ($this->values[$key] === $value) {
                return $this;
            }
        } else {
            if ($value === null && $attribute['allow_null']) {
                return $this;
            }
        }

        $this->change($key, $value);

        return $this;
    }

    /**
     * 把数据合并到Data实例
     * 不允许修改或者不存在的字段会被自动忽略
     *
     * @param array $value
     * @return $this
     */
    public function merge(array $values) {
        foreach ($values as $key => $value) {
            $this->set($key, $value, array('strict' => false));
        }

        return $this;
    }

    /**
     * 获取属性值
     *
     * @param string $key 属性名
     * @return mixed
     * @throws \UnexpectedValueException 当获取不存在的字段
     * @throws \RuntimeException 当字段已经被标记为“废弃”
     */
    public function get($key) {
        if (!$attribute = static::getMapper()->getAttribute($key)) {
            throw new \UnexpectedValueException(get_class() .": Undefined property {$key}");
        }

        if ($attribute['deprecated']) {
            throw new \RuntimeException(get_class() .": Property {$key} is deprecated");
        }

        if (!array_key_exists($key, $this->values)) {
            return Types::factory($attribute['type'])->getDefaultValue($attribute);
        }

        $value = $this->values[$key];
        return is_object($value) ? clone $value : $value;
    }

    /**
     * 获得所有的或指定的属性值，以数组格式返回
     * 自动忽略无效的属性值以及尚未赋值的属性
     *
     * @param mixed... $keys
     * @return mixed[]
     *
     * @example
     * <code>
     * $data->pick();
     * $data->pick('foo', 'bar');
     * $data->pick(array('foo', 'bar'));
     * </code>
     */
    public function pick($keys = null) {
        if ($keys === null) {
            $attributes = static::getMapper()->getAttributes();
            $keys = array();

            foreach ($attributes as $key => $attribute) {
                if (!$attribute['protected']) {
                    $keys[] = $key;
                }
            }
        } else {
            $keys = is_array($keys) ? $keys : func_get_args();
        }

        $values = array();
        foreach ($keys as $key) {
            if (array_key_exists($key, $this->values)) {
                $values[$key] = $this->get($key);
            }
        }

        return $values;
    }

    /**
     * 获得所有的属性值，返回便于json处理的数据格式
     *
     * @return mixed[]
     */
    public function toJSON() {
        $mapper = static::getMapper();
        $json = array();

        foreach ($this->pick() as $key => $value) {
            $attribute = $mapper->getAttribute($key);
            $json[$key] = Types::factory($attribute['type'])->toJSON($value, $attribute);
        }

        return $json;
    }

    /**
     * 此实例是否从未被保存过
     *
     * @return boolean
     */
    public function isFresh() {
        return $this->fresh;
    }

    /**
     * 是否被修改过
     * 可以按照指定的属性名检查
     *
     * @param string $key
     * @return boolean
     */
    public function isDirty($key = null) {
        return $key === null
             ? (bool)$this->dirty
             : isset($this->dirty[$key]);
    }

    /**
     * 获得主键值，如果是多字段主键，以数组方式返回
     *
     * @return string|integer|array
     */
    public function id() {
        $keys = static::getMapper()->getPrimaryKey();
        $id = array();

        foreach ($keys as $key) {
            $value = $this->get($key);

            if (count($keys) === 1) {
                return $value;
            }

            $id[$key] = $value;
        }

        return $id;
    }

    /**
     * 从存储服务内重新获取数据
     * 抛弃所有尚未被保存过的修改
     *
     * @return $this
     */
    public function refresh() {
        return static::getMapper()->refresh($this);
    }

    /**
     * 保存数据到存储服务内
     *
     * @return boolean
     */
    public function save() {
        return static::getMapper()->save($this);
    }

    /**
     * 从存储服务内删除本条数据
     *
     * @return boolean
     */
    public function destroy() {
        return static::getMapper()->destroy($this);
    }

    /**
     * 格式化属性值
     * 可以通过重载此方法实现自定义格式化逻辑
     *
     * @param string $key 属性名
     * @param mixed $value 属性值
     * @param array $attribute 属性定义信息
     * @return mixed 格式化过后的值
     */
    protected function normalize($key, $value, array $attribute) {
        return Types::factory($attribute['type'])->normalize($value, $attribute);
    }

    /**
     * 修改属性值并把被修改的属性标记为被修改过的状态
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    final protected function change($key, $value) {
        $this->values[$key] = $value;
        $this->dirty[$key] = true;
    }

    /**
     * 根据主键值查询生成Data实例
     *
     * @param string|integer|array
     * @return Data|false
     */
    static public function find($id) {
        return static::getMapper()->find($id);
    }

    /**
     * 获得当前Data class的Mapper实例
     *
     * @return Mapper
     */
    final static public function getMapper() {
        $class = static::$mapper;
        
        return $class::factory( get_called_class() );
    }

    /**
     * 获得当前Data class的配置信息
     *
     * @return
     * array(
     *     'service' => (string),
     *     'collection' => (string),
     *     'attributes' => (array),
     *     'readonly' => (boolean),
     *     'strict' => (boolean),
     * )
     */
    final static public function getOptions() {
    	
        $options = array(
            'service' => static::$service,
            'collection' => static::$collection,
            'attributes' => static::$attributes,
            'readonly' => static::$readonly,
            'strict' => static::$strict,
        );

        $called_class = get_called_class();
        if ($called_class == __CLASS__) {
            return $options;
        }

        $parent_class = get_parent_class($called_class);
        $parent_options = $parent_class::getOptions();

        $options['attributes'] = array_merge($parent_options['attributes'], $options['attributes']);
        $options = array_merge($parent_options, $options);

        return $options;
    }
}
