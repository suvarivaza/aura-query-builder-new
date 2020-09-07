<?php


namespace Suvarivaza\AQBN;

use Aura\SqlQuery\QueryFactory;
use PDO;
use PDOException;

class QueryBuilder
{

    private $pdo;
    private $queryFactory;
    protected $prefix = '';
    protected $action;


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


    /*
     * insert
     * @param string $cols
     * @return self
     */
    public function select($cols = '*'){

        $select = $this->queryFactory->newSelect();
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
        $insert = $this->queryFactory->newInsert();
        $this->action =  $insert->into("{$this->prefix}{$table}");
        return $this;
    }


    /*
     * set
     * @param array $data - Array of key-values
     * @return self
     */
    public function set($data){

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
        $this->action->from("{$this->prefix}{$table}");
        return $this;
    }


    /*
     * update
     * @param string $table - table name
     * @return self
     */
    public function update($table)
    {
        $update = $this->queryFactory->newUpdate();
        $this->action = $update->table("{$this->prefix}{$table}");
        return $this;
    }

    /*
    * update
    * @param string $table - table name
    * @return self
    */
    public function delete($table)
    {
        $delete = $this->queryFactory->newDelete();
        $this->action = $delete->from("{$this->prefix}{$table}");
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

        if($fetch === 'one'){
            return $sth->fetch($pdo_fetch_type);
        } else {
            return $sth->fetchAll($pdo_fetch_type);
        }

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

        $operators = ['=', '<', '>', '<=', '>='];
        if (!in_array($operator, $operators)) die('Operator of this type is not supported!');

        $this->action->where("{$column} {$operator} :{$column}")
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

    /*
     * getAll
     * @param string $data_type - can by: 'assoc', 'obj', 'both', 'num'
     * @return all results execute()
     */
    public function getAll($data_type = null)
    {

        return $this->execute(null, $data_type);
    }

    public function getPdo(){
        return $this->pdo;
    }

    public function limit($value){
        $this->action->limit($value);
        return $this;
    }

    public function offset($value){
        $this->action->offset($value);
        return $this;
    }

    public function setPaging($value){
        $this->action->setPaging($value);
        return $this;
    }

    public function page($value){
        $this->action->page($value);
        return $this;
    }

}
