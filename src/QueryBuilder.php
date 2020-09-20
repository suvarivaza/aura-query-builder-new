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
    public static $errors = [];
    private $pdo;
    private static $queryFactory;
    private static $prefix = '';
    private $action;
    private $count;


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
        self::$queryFactory = $QueryFactory; // Object QueryFactory class for database
    }


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

            $pdo = self::getPdo($config);
            $QueryFactory = new QueryFactory('mysql');
            self::$instance = new QueryBuilder($pdo, $QueryFactory);
        }

        return self::$instance;
    }

    private static function getPdo($config)
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
            self::$errors['PDOException'] = $exception->getMessage();
            die($exception->getMessage());
        }

        return $pdo;
    }

    public function errors()
    {
        return self::$errors;
    }


    /*
     * insert
     * @param string $cols
     * @return self
     */
    public function select($cols = '*')
    {

        $select = self::$queryFactory->newSelect();
        $this->action = $select->cols([$cols]);

        return $this;
    }

    /*
     * insert
     * @param string $table - table name
     * @return self
     */
    public function insert($table)
    {
        $insert = self::$queryFactory->newInsert();
        $table = self::$prefix . $table;
        $this->action = $insert->into($table);
        return $this;
    }


    /*
     * set
     * @param array $data - Array of key-values
     * @return self
     */
    public function set($data)
    {

        $this->action->cols($data);

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
        $this->action->fromRaw($table);
        return $this;
    }


    /*
     * update
     * @param string $table - table name
     * @return self
     */
    public function update($table)
    {
        $update = self::$queryFactory->newUpdate();
        $table = self::$prefix . $table;
        $this->action = $update->table($table);
        return $this;
    }

    /*
    * update
    * @param string $table - table name
    * @return self
    */
    public function delete($table)
    {
        $delete = self::$queryFactory->newDelete();
        $table = self::$prefix . $table;
        $this->action = $delete->from($table);
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


        $sth = $this->pdo->prepare($this->action->getStatement());

        $sth->execute($this->action->getBindValues());

        if ($fetch === 'one') {
            $result = $sth->fetch($pdo_fetch_type);
        } else {
            $result = $sth->fetchAll($pdo_fetch_type);
        }

        $this->count = count($result);

        return $result;

    }

    public function getCount()
    {
        return $this->count;
    }

    public function exists()
    {
        return $this->count ? true : false;
    }

    /*
     * where
     * Used in: SELECT, UPDATE, DELETE
     * @param string $column
     * @param string $operator
     * @param string|int $value
     * @return self
     */
    public function where($column, $operator, $value)
    {

        $operators = ['=', '<', '>', '<=', '>=', 'IN'];
        if (!in_array($operator, $operators)) die('Operator of this type is not supported!');

        if ($operator === 'IN') {

            $this->action
                ->where("{$column} {$operator} (:{$column})", [$column => $value]);
            //->bindValue([$column => $value]);
        } else {
            $this->action
                ->where("{$column} {$operator} :{$column}")
                ->bindValue($column, $value);
        }

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

    /*
     * getAll
     * @param string $data_type - can by: 'assoc', 'obj', 'both', 'num'
     * @return all results execute()
     */
    public function getAll($data_type = null)
    {

        return $this->execute(null, $data_type);
    }


    public function limit($value)
    {
        $this->action->limit($value);
        return $this;
    }

    public function offset($value)
    {
        $this->action->offset($value);
        return $this;
    }

    public function orderBy($column)
    {
        $this->action->orderBy([$column]);
        return $this;
    }

    public function setPaging($value)
    {
        $this->action->setPaging($value);
        return $this;
    }

    public function page($value)
    {
        $this->action->page($value);
        return $this;
    }

    public function countFields($table, $where)
    {
        $column = $where[0];
        $operator = $where[1];
        $value = $where[2];

        $count = $this
            ->select('COUNT(*)')
            ->from($table)
            ->where($column, $operator, $value );

        return $count;

    }


    public function testSelect(){
        $select = self::$queryFactory->newSelect();
        $select
            ->cols(['*'])
            ->fromRaw('cththemes_qcomment_projects_orders')
            //->where('project_id = :project_id', ['project_id' => 1733569]);
        ->where('project_id IN (?, ?, ?)', 1733569, 1735642, 1733570);
        //->where('project_id IN (:project_ids)');

        $sth = $this->pdo->prepare($select->getStatement());

        //$sth = $this->pdo->prepare("SELECT * FROM `cththemes_qcomment_projects_orders` WHERE `project_id` IN (:project_ids)");

        $sth->execute($select->getBindValues());
        $result = $sth->fetchAll();
        return $result;
    }

}
