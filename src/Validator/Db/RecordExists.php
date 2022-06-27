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
use Laminas\Log\LoggerInterface;
/**
 * Confirms a record exists in a table.
 */
class RecordExists extends AbstractValidator implements AdapterAwareInterface
{
    use AdapterAwareTrait;


    protected $logger;

    /**
     * Error constants
     */
    const ERROR_NO_RECORD_FOUND = 'noRecordFound';
  

    /**
     * @var array Message templates
     */
    protected $messageTemplates = [
        self::ERROR_NO_RECORD_FOUND => 'No record matching the input was found',
    ];

    /**
     * @var streing - (and | or)
     */
    protected $conditionType;

    /**
     * @var Where
     */
    protected $where;


    protected $byPassValue = null;
    protected $hasByPassValue = false;


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
    
    protected $logger;



    /**
     * Provides basic configuration for use with Laminas\Validator\Db Validators
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


    function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
        return $this;
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
     * @param Where|calleable $where
     * @return $this Provides a fluent interface
     */
    function setWhere($where)
    {
        $this->where = $where;
        return $this;
    }


    /**
     * @return Where|calleable
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





    /**
     * @param mixed $valule
     * @return $this Provides a fluent interface
     */
    function setByPassValue($value)
    {
        $this->byPassValue = $value;
        $this->hasByPassValue = true;
        return $this;
    }

    /**
     * @return mixed
     */
    function getByPassValue()
    {
        return $this->byPassValue;
    }

    /**
     * @return $this Provides a fluent interface
     */
    function removeByPassValue()
    {
        $this->hasByPassValue = false;
        return $this;
    }


    public function isValid($value)
    {
        $this->setValue($value);

        if($this->hasByPassValue)
        {
            if(is_array($this->byPassValue))
            {
                $flag = in_array($value, $this->byPassValue);
                if($flag)
                {
                    return true;
                }
            }
            else
            {
                $flag = ($value == $this->byPassValue);
                if($flag)
                {
                    return true;
                }
            }
        }


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

            if (is_callable($custWhere)) {
                $condition = new Where();
                $custWhere = $custWhere($condition, $value);
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

        if ($this->logger) {
            $this->logger->debug(__CLASS__, $query);
        }

        $result = $adapter->query($query)->execute();

        $row = $result->current();

        if (is_array($row)) {
            $count = $row['count'];
        } else {
            $count =  $row->count;
        }

        $valid = ($count > 0);
        if (!$valid) {
            $this->error(self::ERROR_NO_RECORD_FOUND);
        }

        return $valid;
    }
}
