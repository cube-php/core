<?php

namespace Cube\Modules\Db;

use Cube\Exceptions\DBException;
use Cube\Modules\DB;
use Cube\Modules\Db\DBTable;

class DBTableForeignKey
{
    public const CASCADE = 'cascade';
    public const SET_NULL = 'set null';
    public const RESTRICT = 'restrict';

    /**
     * Table
     *
     * @var DBTable
     */
    protected $table;

    /**
     * Field name
     *
     * @var string
     */
    protected $field_name;

    /**
     * On delete reference option
     *
     * @var string
     */
    protected $on_delete_option = self::CASCADE;

    /**
     * On update reference option
     *
     * @var string
     */
    protected $on_update_option = self::CASCADE;

    /**
     * Reference table
     *
     * @var string
     */
    protected $reference;

    /**
     * Reference primary key
     *
     * @var string
     */
    protected $reference_key;

    /**
     * Class constructor
     *
     * @param DBTable $table
     * @param string $field
     */
    public function __construct(DBTable $table, string $field)
    {
        $this->table = $table;
        $this->field_name = $field;
    }

    public function __destruct()
    {
        $structure = $this->getStructure();

        if(!$structure) {
            return;
        }

        DB::statement($structure);
    }

    /**
     * Set model to reference
     *
     * @param string $model
     * @return self
     */
    public function references(string $model)
    {
        $this->reference = $model::getSchemaName();
        $this->reference_key = $model::getPrimaryKey();
        return $this;
    }

    /**
     * Set on delete reference
     *
     * @param string $reference_option
     * @return self
     */
    public function onDelete(string $reference_option = self::CASCADE)
    {
        $this->on_delete_option = $reference_option;
        return $this;
    }

    /**
     * Set on update reference
     *
     * @param string $reference_option
     * @return self
     */
    public function onUpdate(string $reference_option = self::CASCADE)
    {
        $this->on_update_option = $reference_option;
        return $this;
    }

    /**
     * Generate structure
     *
     * @return string
     */
    protected function getStructure(): ?string
    {
        if(!$this->reference || !$this->reference_key) {
            throw new DBException('No model referenced for foreign key');
        }

        $constraint_name = concat($this->table->getName(), '_', $this->field_name);
        $query = DB::statement(
            'SELECT count(CONSTRAINT_NAME) tcount FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_NAME = ?',
            [$constraint_name]
        )->fetch();

        if($query->tcount) {
            return null;
        }

        $structure = concat(
            'ALTER TABLE ', $this->table->getName(),
            ' ADD CONSTRAINT ', $constraint_name,
            ' FOREIGN KEY (', $this->field_name, ') REFERENCES ',
            $this->reference,
            '(', $this->reference_key, ')',
            ' ON DELETE ', $this->on_delete_option,
            ' ON UPDATE ', $this->on_update_option
        );
        
        return $structure;
    }
}