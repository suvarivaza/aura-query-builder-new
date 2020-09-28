<?php


namespace Suvarivaza\AQBN;

use Aura\SqlQuery\QueryFactory;
use PDO;
use PDOException;

/**
 * @property string error
 */
class QueryBuilder
{

    public static $instance = null;


    private $pdo;

    /**
     * @var \Aura\SqlQuery\QueryFactory $queryFactory
     */
    private $queryFactory;

    /**
     * @var \Aura\SqlQuery\Common\SelectInterface $query
     */
    private $query;

    private static $prefix = '';

    private $action = '';

    private  $errors;

    private $table;

    private $count;


    private function reset(){
        unset($this->query);
        unset($this->count);
        unset($this->table);
    }


    /*
     * Create connection in constructor
     * Gets the argument array with database connection configuration
     *
     * @param PDO connection
     * @param QueryFactory object
     *
     */
    function __construct(PDO $pdo, QueryFactory $QueryFactory)
    {
        $this->pdo = $pdo; // PDO connection
        $this->queryFactory = $QueryFactory; // Object QueryFactory class for database
    }


    /**
     * @param null $config
     * @return QueryBuilder|null
     */
    public static function getInstance($config = null)
    {

        if (!isset(self::$instance)) {

            if (!$config) {

                if (defined('CONFIG_DB_PATH')) {
                    $config = include $_SERVER['DOCUMENT_ROOT'] . CONFIG_DB_PATH;
                } else {
                    $config = include $_SERVER['DOCUMENT_ROOT'] . '/configs/configDb.php';
                }
            }

            $pdo = self::getPdoConnection($config);
            $QueryFactory = new QueryFactory('mysql');
            self::$instance = new QueryBuilder($pdo, $QueryFactory);
        }

        return self::$instance;
    }


    private static function getPdoConnection($config)
    {

        self::$prefix = $config['prefix'];

        try {
            $db_server = $config['host'];
            $db_user = $config['db_user'];
            $db_password = $config['db_password'];
            $db_name = $config['db_name'];
            $charset = $config['charset'];
            $dsn = "mysql:host=$db_server;dbname=$db_name;charset=$charset";
            $options = $config['options'];
            $pdo = new PDO($dsn, $db_user, $db_password, $options);


        } catch (PDOException $exception) {
            die($exception->getMessage());
        }

        return $pdo;
    }



    /*
     * insert
     * @param $cols | string
     * @return self
     */
    public function select($cols = '*')
    {

        $this->reset();
        $this->action = 'select';

        if(!is_array($cols)) $cols = [$cols];

        $select = $this->queryFactory->newSelect();
        $this->query = $select->cols($cols);

        return $this;
    }

    /*
     * insert
     * @param $table | string
     * @return self
     */
    public function insert($table)
    {
        $this->reset();
        $this->action = 'insert';

        $insert = $this->queryFactory->newInsert();
        $table = self::$prefix . $table;
        $this->query = $insert->into($table);
        return $this;
    }


    /*
 * update
 * @param string $table - table name
 * @return self
 */
    public function update($table)
    {

        $this->reset();
        $this->action = 'update';

        $table = self::$prefix . $table;
        $update = $this->queryFactory->newUpdate();
        $this->query = $update->table($table);

        return $this;
    }

    /*
* update
* @param string $table - table name
* @return self
*/
    public function delete($table)
    {
        $this->reset();
        $this->action = 'delete';

        $delete = $this->queryFactory->newDelete();
        $table = self::$prefix . $table;
        $this->query = $delete->from($table);

        return $this;
    }

    /*
     * set
     * @param array $data - Array of key-values
     * @return self
     */
    public function set($data)
    {

        $this->query->cols($data);

        return $this;
    }

    /*
     * from
     * @param string $table - table name
     * @return self
     */
    public function from($table)
    {

        $table = self::$prefix . $table;
        $this->query->fromRaw($table);

        $this->table = $table;

        return $this;
    }



    /*
     * execute
     * Used in: INSERT, UPDATE, DELETE
     * @param string $fetch
     * @param string $data_type can by: 'assoc', 'obj', 'both', 'num'
     * @return self
     */
    public function execute($fetch = null, $data_type = null)
    {


        $pdo_fetch_types = [
            'assoc' => PDO::FETCH_ASSOC,
            'obj' => PDO::FETCH_OBJ,
            'both' => PDO::FETCH_BOTH,
            'num' => PDO::FETCH_NUM,
        ];

        if ($data_type) {
            $pdo_fetch_type = $pdo_fetch_types[$data_type];
        } else {
            $pdo_fetch_type = PDO::FETCH_ASSOC;
        }

        try {

            $sth = $this->pdo->prepare($this->query->getStatement());
            $result = $sth->execute($this->query->getBindValues());

            if ($fetch === 'one') {
                $result = $sth->fetch($pdo_fetch_type);
            } else if ($fetch === 'all') {
                $result = $sth->fetchAll($pdo_fetch_type);
            }


        } catch (PDOException $exception) {
            $this->errors = $exception->getMessage();
            die($exception->getMessage());
        }

        return $result;

    }



    /**
     * @param $were | string can any query after were (attention! not safe!)
     * @return self
     */
    public function customWere($were){
        $this->query
            ->where($were);
        return $this;
    }

    /*
     * where
     * Used in: SELECT, UPDATE, DELETE
     * @param string $column | string
     * @param string $operator | string - mast by: '=', '<', '>', '<=', '>='
     * @param string|int $value | string | int
     * @return self
     */
    public function where($column, $operator, $value)
    {
        if($this->action === 'select' and !$this->table) {
            die("QueryBuilder where() ERROR! The SELECT query type must contain calling from('table') method before calling the method where()!");
        }

        $operators = ['=', '<', '>', '<=', '>=', 'IN'];
        if (!in_array($operator, $operators)) die('Operator of this type is not supported!');

        if ($operator === 'IN') {

            $in = implode(',', $value);
            $this->query
                ->where("{$column} {$operator} ({$in})");

            foreach ($value as &$val) {
                $val = $this->pdo->quote($val); //iterate through array and quote
            }

        } else {
            $this->query
                ->where("{$column} {$operator} :{$column}")
                ->bindValue($column, $value);
        }

        return $this;
    }

    /**
     * @param $column | string
     * @param $operator | string - mast by: '=', '<', '>', '<=', '>='
     * @param $value | string | int
     * @return self
     */
    public function orWhere($column, $operator, $value)
    {

        $operators = ['=', '<', '>', '<=', '>='];
        if (!in_array($operator, $operators)) die('Operator of this type is not supported!');

        $this->query
            ->orWhere("{$column} {$operator} :{$column}")
            ->bindValue($column, $value);

        return $this;
    }

    /*
     * getOne
     * @param string $data_type can by: 'assoc', 'obj', 'both', 'num'
     * @return one result execute()
     */
    public function getOne($data_type = null)
    {
        return $this->execute('one', $data_type);
    }


    /**
     * @return bool
     */
    public function exists()
    {
        return $this->execute('one') ? true : false;
    }

    /*
     * getAll
     * @param $data_type | string : can by: 'assoc', 'obj', 'both', 'num'
     * @return all results execute()
     */
    public function getAll($data_type = null)
    {
        return $this->execute('all', $data_type);
    }

    public function getValue($column = null)
    {
        if(!$column)
        {
            $cols = $this->query->getCols();
            $column = $cols[0];
        }

        return $this->execute('one')[$column];
    }


    /**
     * @param $value
     * @return self
     */
    public function limit($value)
    {
        $this->query->limit($value);
        return $this;
    }

    /**
     * @param $value
     * @return self
     */
    public function offset($value)
    {
        $this->query->offset($value);
        return $this;
    }


    /**
     * @param $column
     * @return $this
     */
    public function orderBy($column)
    {
        $this->query->orderBy([$column]);
        return $this;
    }


    /**
     * @param $value
     * @return $this
     */
    public function setPaging($value)
    {
        $this->query->setPaging($value);
        return $this;
    }


    /**
     * @param $value
     * @return $this
     */
    public function page($value)
    {
        $this->query->page($value);
        return $this;
    }


    /**
     * @return $count | int :return the count of rows satisfying the selection condition
     */
    public function getCount()
    {
        $this->query->cols(['COUNT(*)']);
        $count = $this->execute('all')[0]["COUNT(*)"];
        return $count;
    }


    /**
     * @param $field | string
     * @return $sum | int :return the sum of values satisfying the selection condition
     */
    public function getSum($field)
    {
        $this->query->cols(["SUM({$field})"]);
        $sum = $this->execute('all')[0]["SUM({$field})"];
        return $sum;
    }


    /**
     * @return mixed
     */
    public function error()
    {
        return $this->errors;
    }




}
