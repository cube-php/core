<?php

namespace Cube\Modules\Db;

use InvalidArgumentException;

use Cube\Modules\DB;
use Cube\Modules\Db\DBTable;

class DBSchemaBuilder
{

    /**
     * Schema data types
     * 
     * @var array[]
     */
    private static $data_types = array(
        'enum' => [],
        'blob' => [],
        'text' => [],
        'mediumblob' => [],
        'mediumtext' => [],
        'longblob' => [],
        'longtext' => [],
        'date' => [],
        'datetime' => [],
        'timestamp' => [],
        'time' => [],
        'year' => [],
        'int' => array(
            'min_length' => 10
        ),
        'tinyint' => array(
            'min_length' => 4
        ),
        'smallint' => array(
            'min_length' => 10
        ),
        'mediumint' => array(
            'min_length' => 10
        ),
        'bigint' => array(
            'min_length' => 10
        ),
        'float' => array(
            'min_length' => 10
        ),
        'double' => array(
            'min_length' => 10
        ),
        'decimal' => array(
            'min_length' => 10
        ),
        'char' => array(
            'min_length' => 225
        ),
        'varchar' => array(
            'min_length' => 225
        ),
        'tinytext' => array(
            'min_length' => 225
        ),
        'tinyblob' => array(
            'min_length' => 225
        )
    );

    /**
     * Schema name
     * 
     * @var string
     */
    private $name;

    /**
     * Whether or not field should be added to table
     * 
     * @var bool
     */
    private $add_field;

    /**
     * Table
     * 
     * @var \Cube\Modules\DB\DBTable
     */
    private $table;

    /**
     * Schema structure
     * 
     * @var string
     */
    private $structure = '';

    /**
     * Nullable
     * 
     * @var bool
     */
    private $nullable = false;

    /**
     * Auto Increment
     * 
     * @var bool
     */
    private $increment = false;

    /**
     * Schema type
     * 
     * @var string
     */
    private $type;

    /**
     * Primary key
     * 
     * @var bool
     */
    private $primary = false;

    /**
     * Length
     * 
     * @var string|null
     */
    private $length = null;

    /**
     * Schema attribute
     * 
     * @var string|null
     */
    private $attribute = null;

    /**
     * Schema default values
     * 
     * @var string|null
     */
    private $default = null;

    /**
     * Class constructor
     * 
     * @param string $table
     * @param string $schema_name
     */
    public function __construct(DBTable $table, $schema_name, $add_field = true)
    {
        $this->table = $table;

        $this->name = $schema_name;

        $this->add_field = $add_field;

        if(!$this->table->exists() && $this->add_field) {
            throw new InvalidArgumentException($this->table->getName() . ' Not found');
        }
    }

    /**
     * Set schema type
     * 
     * @param string $name Data type name
     * 
     * @return self
     * 
     * @throws \Exception
     */
    public function __call($name, $args)
    {

        if($this->type)
            throw new InvalidArgumentException
                ('Data type "' .$this->type. '" has already been specified for field "' . $this->name . '"');

        $data_types = static::$data_types;
        $name = strtolower($name);
        $allowed = array_keys($data_types);
        $num_args = count($args);

        #if the specified data is not identified throw exception
        if(!in_array($name, $allowed)) {
            throw new \Exception('Unknown data type ' . $name);
        }

        if($num_args) $args = array_map([$this, 'passCheck'], $args);

        #The only args that can be passed in is length so...
        $length = $num_args ? implode(',', $args) : null;

        #If no length is specified,
        #Check if the specified data type has default length
        $length = $length ?? $data_types[$name]['min_length'] ?? null;

        #Set the datatype
        $this->setType($name);

        #Set length
        $this->length = $length;

        return $this;
    }

    /**
     * Finalize actions on destruct
     * 
     * @return void
     */
    public function __destruct()
    {

        #Developer doesn't want the field added
        #Perhaps just wants the structure
        if(!$this->add_field) return;

        #Do nothing if the field name already exists
        if($this->table->hasField($this->name)) return;

        #Go on and add the field
        $this->table->addField($this->getStructure());

        #Since another field has been added,
        #Temporary field should be removed
        $temp_field = $this->table->temp_field_name;

        if($this->name !==  $temp_field && $this->table->hasField($temp_field)) {
            $this->table->removeTempField();
        }
    }

    /**
     * Set schema attribute to binary
     * 
     * @return self
     */
    public function binary()
    {
        $this->attribute = 'binary';
        return $this;
    }

    /**
     * Set default value for field
     * 
     * @param string $default_value
     * 
     * @return self
     */
    public function default($default_value)
    {
        $this->default = DB::escape($default_value);
        return $this;
    }

    /**
     * Set schema to increment
     * 
     * @return self
     */
    public function increment()
    {
        $this->primary = true;
        $this->increment = true;
        return $this;
    }

    /**
     * Make shema nullable
     * 
     * @return self
     */
    public function nullable()
    {
        $this->nullable = true;
        return $this;
    }
    
    /**
     * Set schema as primary key
     * 
     * @return self
     */
    public function primary()
    {
        if(!$this->type) {
            $this->int();
        }

        $this->primary = true;
        return $this;
    }

    /**
     * Set schema attribute to unsigned
     * 
     * @return self
     */
    public function unsigned()
    {
        $this->attribute = 'unsigned';
        return $this;
    }

    /**
     * Set schema as a boolean
     *
     * @param boolean $default
     * @return self
     */
    public function boolean(bool $default = true)
    {
        $this->tinyint(1)->default((int) $default);
        return $this;
    }

    /**
     * Get schema structure
     * 
     * @return string
     */
    public function getStructure()
    {

        $structure = "{$this->name} {$this->type}";

        #Add length structure
        if($this->length) $structure .= "($this->length)";

        #Let's check for attributes
        if($this->attribute) $structure .= ' ' . $this->attribute;

        #Check if field is nullable
        $structure .= ($this->nullable) ? ' null' : ' not null';

        #Check for default value
        if($this->default) $structure .= " default {$this->default}";

        #Check if primary key
        if($this->primary) $structure .= ' primary key';

        #Check for auto increment
        if($this->increment) $structure .= ' auto_increment';

        return $structure;
    }

    /**
     * Pass check
     * 
     * @param string $string string to check
     * 
     * @return string checked string
     */
    private function passCheck($string)
    {
        return is_numeric($string) ? $string : "'$string'";
    }

    /**
     * Set schema type
     * 
     * @return void
     */
    private function setType($type)
    {
        $this->type = $type;
    }
}