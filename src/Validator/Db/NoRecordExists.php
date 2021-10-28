<?php
namespace Com\Validator\Db;

use Laminas\Db\Adapter\AdapterAwareInterface;
use Laminas\Db\Adapter\AdapterAwareTrait;
use Laminas\Validator\Exception;
use Laminas\Db\Sql\Where;
use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Literal;
use Laminas\Validator\AbstractValidator;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Adapter\Adapter as DbAdapter;
/**
 * Confirms a record DOES NOT exists in a table.
 */
class NoRecordExists extends AbstractValidator implements AdapterAwareInterface
{
    use AdapterAwareTrait;

    /**
     * Error constants
     */
    const ERROR_RECORD_FOUND    = 'recordFound';
  

    /**
     * @var array Message templates
     */
    protected $messageTemplates = [
        self::ERROR_RECORD_FOUND => 'A record matching the input was found',
    ];

    /**
     * @var streing - (and | or)
     */
    protected $conditionType;

    /**
     * @var Where
     */
    protected $where;


    /**
     * @var string
     */
    protected $schema = null;

    /**
     * @var string
     */
    protected $table = '';

    /**
     * @var string
     */
    protected $field = '';



    /**
     * Provides basic configuration for use with Laminas\Validator\Db Validators
     * Setting $exclude allows a single record to be excluded from matching.
     * Exclude can either be a String containing a where clause, or an array with `field` and `value` keys
     * to define the where clause added to the sql.
     * A database adapter may optionally be supplied to avoid using the registered default adapter.
     *
     * The following option keys are supported:
     * 'table'   => The database table to validate against
     * 'schema'  => The schema keys
     * 'field'   => The field to check for a match
     * 'where'   => An optional Where clause
     * 'adapter' => An optional database adapter to use
     *
     * @param array|Traversable|Select $options Options to use for this validator
     * @throws \Laminas\Validator\Exception\InvalidArgumentException
     */
    public function __construct($options = null)
    {
        parent::__construct($options);

        if ($options instanceof Traversable) {
            $options = ArrayUtils::iteratorToArray($options);
        }

        if (is_array($options)) {
            if (array_key_exists('adapter', $options)) {
                $this->setAdapter($options['adapter']);
            }

            if (array_key_exists('field', $options)) {
                $this->setField($options['field']);
            }
            
            if (array_key_exists('table', $options)) {
                $this->setTable($options['table']);
            }

            if (array_key_exists('schema', $options)) {
                $this->setSchema($options['schema']);
            }

            if (array_key_exists('where', $options)) {
                $this->setWhere($options['where']);
            }

            if (array_key_exists('condition_type', $options)) {
                $this->setConditionType($options['condition_type']);
            }
        }
    }




    /**
     * Returns the set field
     *
     * @return string
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * Sets a new field
     *
     * @param string $field
     * @return $this
     */
    public function setField($field)
    {
        $this->field  = (string) $field;
        return $this;
    }

    /**
     * Returns the set table
     *
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Sets a new table
     *
     * @param string $table
     * @return $this Provides a fluent interface
     */
    public function setTable($table)
    {
        $this->table  = (string) $table;
        return $this;
    }

    /**
     * Returns the set schema
     *
     * @return string
     */
    public function getSchema()
    {
        return $this->schema;
    }

    /**
     * Sets a new schema
     *
     * @param string $schema
     * @return $this Provides a fluent interface
     */
    public function setSchema($schema)
    {
        $this->schema = $schema;
        return $this;
    }


    /**
     * @param Where $where
     * @return $this Provides a fluent interface
     */
    function setWhere(Where $where)
    {
        $this->where = $where;
        return $this;
    }


    /**
     * @return Where
     */
    function getWhere()
    {
        return $this->where;
    }


    /**
     * @param string $conditionType - (OR | AND)
     * @return $this Provides a fluent interface
     */
    function setConditionType($conditionType)
    {
        $this->conditionType = strtolower($conditionType);
        return $this;
    }


    /**
     * @return string
     */
    function getConditionType()
    {
        return $this->conditionType;
    }


    /**
     * Returns the set adapter
     *
     * @throws \Laminas\Validator\Exception\RuntimeException When no database adapter is defined
     * @return DbAdapter
     */
    public function getAdapter()
    {
        return $this->adapter;
    }

    /**
     * Sets a new database adapter
     *
     * @param  DbAdapter $adapter
     * @return self Provides a fluent interface
     */
    public function setAdapter(DbAdapter $adapter)
    {
        return $this->setDbAdapter($adapter);
    }



    public function isValid($value)
    {
        $this->setValue($value);

        /*
         * Check for an adapter being defined. If not, throw an exception.
         */
        if (null === $this->adapter) {
            throw new Exception\RuntimeException('No database adapter present');
        }

        $tableName = $this->getTable();
        $schema    = $this->getSchema();
        if ($schema) {
            $tableName = "{$schema}.{$tableName}";
        }
        
        $select = new Select();
        $select->from($tableName);
        $select->columns([
            'count' => new Literal('COUNT(*)')
        ]);

        $field = $this->getField();
        $custWhere = $this->getWhere();

        if (!$custWhere) {
            $where = new Where();
            $where->equalTo($field, $value);
        } else {
            $condType = $this->getConditionType();
            if (empty($condType) || !in_array($condType, ['and', 'or'])) {
                $condType = 'and';
            }

            $where = new Where();
            $where->equalTo($field, $value);
            if ('and' == $condType) {
                $where->addPredicate($custWhere);
            } else {
                $where->orPredicate($custWhere);
            }
        }

        $select->where($where);

        #
        $adapter = $this->getAdapter();
        $sql = new Sql($adapter);
        $query = $sql->buildSqlString($select);
        $result = $adapter->query($query)->execute();

        $row = $result->current();

        if (is_array($row)) {
            $count = $row['count'];
        } else {
            $count =  $row->count;
        }

        $valid = (0 == $count);
        if (!$valid) {
            $this->error(self::ERROR_RECORD_FOUND);
        }

        return $valid;
    }
}
