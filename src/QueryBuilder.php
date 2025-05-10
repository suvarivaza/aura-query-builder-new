<?php
//https://github.com/auraphp/Aura.SqlQuery/blob/HEAD/docs/index.md

namespace Suvarivaza\AQBN;

use Aura\SqlQuery\QueryFactory;
use Aura\SqlQuery\Common\SelectInterface;
use Aura\SqlQuery\Common\UpdateInterface;
use Aura\SqlQuery\Common\InsertInterface;
use Aura\SqlQuery\Common\DeleteInterface;
use PDO;
use PDOException;
use App\QueryBuilderException;

defined('_JEXEC') or die('Restricted access');

/**
 * @property string error
 */
class QueryBuilder
{

    /**
     * @var QueryBuilder|null
     */
    private static ?QueryBuilder $instance = null;

    private $prefix = '';

    /**
     * @var PDO|null
     */
    private ?PDO $pdo;

    /**
     * @var QueryFactory|null
     */
    private ?QueryFactory $queryFactory;

    /**
     * @var SelectInterface|UpdateInterface|InsertInterface|DeleteInterface|null
     */
    private $query = null;

    private string $action = '';

    private string $error = '';

    private string $sql = '';

    private array $values = [];

    private function reset()
    {
        //сброс
        $this->query = null;
        $this->action = '';
        $this->error = '';
        $this->sql = '';
        $this->values = [];
    }


    /*
     * Implements the singleton pattern!
     *
     * Create connection in constructor
     * Gets the argument array with database connection configuration
     *
     * @param PDO connection
     * @param QueryFactory object
     *
     */
    private function __construct(PDO $pdo, QueryFactory $QueryFactory, string $prefix)
    {
        $this->pdo = $pdo; // PDO connection
        $this->queryFactory = $QueryFactory; // Aura\SqlQuery\QueryFactory
        $this->prefix = $prefix;
    }


    /**
     * @param $configDb
     * @return QueryBuilder
     * @throws QueryBuilderException
     */
    public static function getInstance($configDb = null): QueryBuilder
    {

        if (self::$instance === null) {

            if (!$configDb) {

                if (defined('CONFIG_DB_PATH')) {
                    $configDb = include $_SERVER['DOCUMENT_ROOT'] . CONFIG_DB_PATH;
                } else {
                    $configDb = include $_SERVER['DOCUMENT_ROOT'] . '/configs/configDb.php'; // default path to db config file
                }
            }

            [$pdo, $prefix] = self::getPdoConnection($configDb);
            $QueryFactory = new QueryFactory('mysql');
            self::$instance = new self($pdo, $QueryFactory, $prefix);
        }

        return self::$instance;
    }


    /**
     * @param $configDb
     * @return array
     * @throws QueryBuilderException
     */
    private static function getPdoConnection($configDb)
    {

        $prefix = $configDb['prefix'];

        try {
            $db_server = $configDb['host'];
            $db_user = $configDb['db_user'];
            $db_password = $configDb['db_password'];
            $db_name = $configDb['db_name'];
            $charset = $configDb['charset'];
            $dsn = "mysql:host=$db_server;dbname=$db_name;charset=$charset";
            $options = $configDb['options'];
            $pdo = new PDO($dsn, $db_user, $db_password, $options);

        } catch (PDOException $exception) {
            throw new QueryBuilderException($exception->getMessage());
        }

        return [$pdo, $prefix];
    }

    private function __clone()
    {
    }

    private function __wakeup()
    {
    }


    public function getPdo(): PDO
    {
        return $this->pdo;
    }


    /**
     * Start a SELECT query
     *
     * @param string|array|null $cols Columns to select. If null, selects all columns (*)
     * @return self
     *
     * Examples:
     * // Select all columns
     * $db->select()->from('users')->getAll();
     *
     * // Select specific columns
     * $db->select(['id', 'name', 'email'])->from('users')->getAll();
     *
     * // Select with aliases
     * $db->select(['id', 'name AS username', 'email'])->from('users')->getAll();
     *
     * // Select with count
     * $db->select(['COUNT(*) as total'])->from('users')->getValue('total');
     *
     * // Select with joins
     * $db->select(['users.id', 'users.name', 'orders.amount'])
     *    ->from('users')
     *    ->join('LEFT', 'orders', 'users.id = orders.user_id')
     *    ->getAll();
     */
    public function select($cols = null): self
    {
        $this->reset();
        $this->action = 'select';

        if (!$cols) $cols = '*';
        if (!is_array($cols)) $cols = [$cols];

        $select = $this->queryFactory->newSelect();
        $this->query = $select->cols($cols);

        return $this;
    }


    /**
     * @param $cols
     * @return $this
     *
     * $select->cols([
     * 'id',                       // column name
     * 'name AS namecol',          // one way of aliasing
     * 'col_name' => 'col_alias',  // another way of aliasing
     * 'COUNT(foo) AS foo_count'   // embed calculations directly
     * ])
     */
    public function cols($cols = '*'): self
    {
        if (!is_array($cols)) $cols = [$cols];
        $this->query->cols($cols);
        return $this;
    }


    /**
     * Start an INSERT query
     *
     * @param string $table Table name
     * @return self
     *
     * Examples:
     * // Simple insert
     * $db->insert('users')
     *    ->set(['name' => 'John', 'email' => 'john@example.com'])
     *    ->execute();
     *
     * // Insert with raw SQL value
     * $db->insert('users')
     *    ->set('created_at', 'NOW()')
     *    ->execute();
     *
     * // Insert multiple rows
     * $db->insert('users')
     *    ->set(['name' => 'John', 'email' => 'john@example.com'])
     *    ->execute();
     * $db->insert('users')
     *    ->set(['name' => 'Jane', 'email' => 'jane@example.com'])
     *    ->execute();
     */
    public function insert($table): self
    {
        $this->reset();
        $this->action = 'insert';

        $insert = $this->queryFactory->newInsert();
        $this->query = $insert->into($this->prefix . $table);
        return $this;
    }


    /**
     * Start an UPDATE query
     *
     * @param string $table Table name
     * @return self
     *
     * Examples:
     * // Simple update
     * $db->update('users')
     *    ->set(['name' => 'John Doe'])
     *    ->where('id', '=', 1)
     *    ->execute();
     *
     * // Update with conditions
     * $db->update('users')
     *    ->set(['status' => 'active'])
     *    ->where('last_login', '<', '2023-01-01')
     *    ->execute();
     *
     * // Update with raw SQL value
     * $db->update('users')
     *    ->set('last_login', 'NOW()')
     *    ->where('id', '=', 1)
     *    ->execute();
     */
    public function update($table): self
    {
        $this->reset();
        $this->action = 'update';

        $table = $this->prefix . $table;
        $update = $this->queryFactory->newUpdate();
        $this->query = $update->table($table);

        return $this;
    }

    /**
     * Start a DELETE query
     *
     * @param string $table Table name
     * @return self
     *
     * Examples:
     * // Simple delete
     * $db->delete('users')
     *    ->where('id', '=', 1)
     *    ->execute();
     *
     * // Delete with multiple conditions
     * $db->delete('users')
     *    ->where('status', '=', 'inactive')
     *    ->where('last_login', '<', '2023-01-01')
     *    ->execute();
     *
     * // Delete with IN condition
     * $db->delete('users')
     *    ->where('id', 'IN', [1, 2, 3])
     *    ->execute();
     */
    public function delete($table): self
    {
        $this->reset();
        $this->action = 'delete';

        $delete = $this->queryFactory->newDelete();
        $table = $this->prefix . $table;
        $this->query = $delete->from($table);

        return $this;
    }


    /**
     * @param $data
     * @param $originalSqlString
     * @return $this
     * @throws QueryBuilderException
     */
    public function set($data, $originalSqlString = ''): self
    {
        if (!$this->query) {
            throw new QueryBuilderException("Query not initialized. Call insert() or update() first.");
        }

        if (!($this->query instanceof InsertInterface) && !($this->query instanceof UpdateInterface)) {
            throw new QueryBuilderException("Method ->set() can be used only for UPDATE and INSERT queries!");
        }

        if (is_array($data)) {
            $this->query->cols($data);
        } elseif ($originalSqlString) {
            $this->query->set($data, $originalSqlString);
        }

        return $this;
    }

    /**
     * @param $table
     * @return $this
     * @throws QueryBuilderException
     */
    public function from($table): self
    {
        if (!$this->query) {
            throw new QueryBuilderException("Query not initialized. Call select() first.");
        }

        if ($this->action !== 'select') {
            throw new QueryBuilderException("Method from() can be used only in SELECT queries!");
        }

        if (is_array($table)) {
            $tables = $table;
            $table = '';
            foreach ($tables as $t) {
                $table .= $this->prefix . $t . ',';
            }
            $table = substr($table, 0, -1);
        } else {
            $table = $this->prefix . $table;
        }

        $this->query->fromRaw($table);
        return $this;
    }


    /**
     * @param $fetch
     * @param $data_type
     * @return array|bool|mixed|string
     * @throws QueryBuilderException
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
            // Определяем источник SQL запроса
            if ($this->query) {
                // SQL сформирован через Query Builder
                $sql = $this->query->getStatement();
                $values = $this->query->getBindValues();
            } elseif ($this->sql) {
                // SQL установлен вручную через метод query()
                $sql = $this->sql;
                $values = $this->values;
            } else {
                throw new QueryBuilderException("No SQL query defined. Use Query Builder methods or query() method first.");
            }

            if (empty($sql)) {
                throw new QueryBuilderException("Empty SQL query");
            }

            // Логирование SQL запроса (можно включить в режиме отладки)
            if (defined('DEBUG_MODE') && constant('DEBUG_MODE')) {
                error_log("SQL: " . $sql);
                error_log("Values: " . print_r($values, true));
            }

            $sth = $this->pdo->prepare($sql);
            $result = $sth->execute($values);

            if ($this->action === 'insert') {
                return $this->pdo->lastInsertId();
            }

            if ($fetch === 'one') {
                $result = $sth->fetch($pdo_fetch_type);
            } elseif ($fetch === 'all') {
                $result = $sth->fetchAll($pdo_fetch_type);
            }

            return $result;

        } catch (PDOException $exception) {
            $this->error = 'ERROR! Database QueryBuilder: ' . $exception->getMessage();
            throw new QueryBuilderException("ERROR! Database QueryBuilder: " . $exception->getMessage(), 0, $exception);
        }
    }


    /**
     * Execute raw SQL query
     *
     * @param string $sql Raw SQL query
     * @param array $values Parameters for prepared statement
     * @return array|bool|mixed|string
     * @throws QueryBuilderException
     *
     * Example:
     * $db->query("SELECT * FROM users WHERE id = :id", ['id' => 1]);
     * $db->query("INSERT INTO users (name, email) VALUES (:name, :email)",
     *     ['name' => 'John', 'email' => 'john@example.com']);
     */
    public function query($sql, $values = [])
    {
        $this->reset();
        $this->sql = $sql;
        $this->values = $values;
        return $this->execute();
    }


    /**
     * @param $sql
     * @return $this
     */
    public function setQuery($sql, $values = []): self
    {
        //$db->setQuery($sql)->getAll()
        $this->reset();

        $this->sql = $sql;
        $this->values = $values;
        return $this;
    }


    /**
     * @return $this
     */
    public function unionAll(): self
    {
        $this->query->unionAll();
        return $this;
    }

    /**
     * @return $this
     */
    public function union(): self
    {
        $this->query->union();
        return $this;
    }

    /**
     * @param $column
     * @param $value
     * @return $this
     */
    public function bindValue($column, $value): self
    {
        $this->query->bindValue($column, $value);
        return $this;
    }

    /**
     * @param $values
     * @return $this
     */
    public function bindValues($values = []): self
    {
        $this->query->bindValues($values);
        return $this;
    }


    /**
     * @param $were
     * @param $value
     * @return $this
     */
    public function customWhere($were, $value = []): self
    {
        //->where('zim = :zim', ['zim' => 'zim_val'])

        $this->query
            ->where($were, $value);
        return $this;
    }


    /**
     * Add a WHERE condition to the query
     *
     * @param string $column Column name
     * @param string $operator Operator (=, <, >, <=, >=, !=, IN, NOT IN, LIKE, NOT LIKE, IS NULL, IS NOT NULL)
     * @param mixed $value Value to compare
     * @return self
     * @throws QueryBuilderException
     *
     * Examples:
     * // Basic comparison
     * $db->where('id', '=', 1);
     *
     * // LIKE operator
     * $db->where('name', 'LIKE', '%John%');
     *
     * // NOT LIKE operator
     * $db->where('email', 'NOT LIKE', '%@gmail.com');
     *
     * // IS NULL
     * $db->where('deleted_at', 'IS NULL');
     *
     * // IS NOT NULL
     * $db->where('updated_at', 'IS NOT NULL');
     *
     * // IN operator
     * $db->where('id', 'IN', [1, 2, 3]);
     *
     * // NOT IN operator
     * $db->where('status', 'NOT IN', ['inactive', 'deleted']);
     */
    public function where($column, $operator, $value = null)
    {
        if (!$this->query) {
            throw new QueryBuilderException("Query not initialized. Call select(), update(), or delete() first.");
        }

        // Если передано только два параметра, считаем что оператор '='
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        // Список поддерживаемых операторов
        $operators = [
            '=', '<', '>', '<=', '>=', '!=',
            'IN', 'NOT IN',
            'LIKE', 'NOT LIKE',
            'IS NULL', 'IS NOT NULL'
        ];

        if (!in_array($operator, $operators)) {
            throw new QueryBuilderException("Operator {$operator} is not supported!");
        }

        // Обработка NULL операторов
        if ($operator === 'IS NULL' || $operator === 'IS NOT NULL') {
            $this->query->where("{$column} {$operator}");
            return $this;
        }

        // Обработка IN и NOT IN
        if (($operator === 'IN' || $operator === 'NOT IN') && is_array($value)) {
            $placeholders = [];
            foreach ($value as $i => $val) {
                $ph = "{$column}_in_{$i}";
                $placeholders[] = ":$ph";
                $this->query->bindValue($ph, $val);
            }
            $this->query->where("{$column} {$operator} (" . implode(',', $placeholders) . ")");
            return $this;
        }

        // Обработка LIKE и NOT LIKE
        if ($operator === 'LIKE' || $operator === 'NOT LIKE') {
            $this->query->where("{$column} {$operator} :{$column}")
                ->bindValue($column, $value);
            return $this;
        }

        // Обработка стандартных операторов сравнения
        $this->query->where("{$column} {$operator} :{$column}")
            ->bindValue($column, $value);

        return $this;
    }

    /**
     * @param $column
     * @param $operator
     * @param $value
     * @return $this
     * @throws QueryBuilderException
     */
    public function orWhere($column, $operator, $value): self
    {

        $operators = ['=', '<', '>', '<=', '>='];
        if (!in_array($operator, $operators)) {
            throw new QueryBuilderException("Operator {$operator} is not supported!");
        }

        $this->query
            ->orWhere("{$column} {$operator} :{$column}")
            ->bindValue($column, $value);

        return $this;
    }

    /**
     * @param $data_type
     * @return array|bool|mixed|string
     * @throws QueryBuilderException
     */
    public function getOne($data_type = null)
    {
        return $this->execute('one', $data_type);
    }


    /**
     * @param $data_type
     * @return array|bool|mixed|string
     * @throws QueryBuilderException
     */
    public function getAll($data_type = null)
    {
        return $this->execute('all', $data_type);
    }


    /**
     * @param $column
     * @return mixed|string
     * @throws QueryBuilderException
     */
    public function getValue($column = null)
    {
        if ($column) {
            $this->query->resetCols();
            $this->cols($column);
        } else {
            $cols = $this->query->getCols();
            $column = $cols[0];
        }

        return $this->execute('one')[$column];
    }

    public function getValueTest($column = null)
    {
        if ($column) {
            $this->query->resetCols();
            $this->cols($column);
        } else {
            $cols = $this->query->getCols();
            $column = $cols[0];
        }

        $result = $this->execute('one');

        return $result[$column];
    }


    /**
     * @return bool
     * @throws QueryBuilderException
     */
    public function exists()
    {
        return $this->execute('one') ? true : false;
    }


    /**
     * @param $value
     * @return $this
     */
    public function limit($value): self
    {
        if (!$this->query) {
            throw new QueryBuilderException("Query not initialized. Call select() first.");
        }
        $this->query->limit($value);
        return $this;
    }


    /**
     * @param $value
     * @return $this
     */
    public function offset($value): self
    {
        if (!$this->query) {
            throw new QueryBuilderException("Query not initialized. Call select() first.");
        }
        $this->query->offset($value);
        return $this;
    }


    /**
     * @param $column
     * @return $this
     */
    public function orderBy($column, $ordering = 'ASC'): self
    {
        if (!$this->query) {
            throw new QueryBuilderException("Query not initialized. Call select() first.");
        }
        $this->query->orderBy([$column . ' ' . $ordering]);
        return $this;
    }


    /**
     * @param $value
     * @return $this
     */
    public function setPaging($value): self
    {
        $this->query->setPaging($value);
        return $this;
    }


    /**
     * @param $value
     * @return $this
     */
    public function page($value): self
    {
        $this->query->page($value);
        return $this;
    }


    /**
     * @return mixed|string
     * @throws QueryBuilderException
     */
    public function getCount()
    {
        $this->query->cols(['COUNT(*)']);
        return $this->execute('all')[0]["COUNT(*)"];
    }


    /**
     * @param $field
     * @return mixed|string
     * @throws QueryBuilderException
     */
    public function getSum($field)
    {
        $this->query->cols(["SUM({$field})"]);
        return $this->execute('all')[0]["SUM({$field})"];
    }


    /**
     * @return mixed
     */
    public function getError()
    {
        return $this->error;
    }


    /**
     * @param $type
     * @param $table
     * @param $conditions
     * @return $this
     */
    public function join($type, $table, $conditions): self
    {

        $this->action = 'join';

        $this->query->join(
            $type,  // тип соединения
            $this->prefix . $table,  // присоединяемся к этой таблице ...
            $conditions  // ... НА этих условиях
        );

        return $this;
    }



    /**
     * Begins a transaction.
     * @return bool
     * @throws QueryBuilderException
     *
     * Example of using transactions:
     *
     * try {
     *     $db->beginTransaction();
     *     $db->insert('users')->set(['name' => 'John', 'email' => 'john@example.com'])->execute();
     *     $db->insert('orders')->set(['user_id' => 1, 'amount' => 100])->execute();
     *     $db->commit();
     * } catch (QueryBuilderException $e) {
     *     $db->rollback();
     *     echo "Error: " . $e->getMessage();
     * }
     */
    public function beginTransaction(): bool
    {
        try {
            return $this->pdo->beginTransaction();
        } catch (PDOException $e) {
            throw new QueryBuilderException("Failed to begin transaction: " . $e->getMessage());
        }
    }

    /**
     * Commits a transaction.
     * @return bool
     * @throws QueryBuilderException
     */
    public function commit(): bool
    {
        try {
            return $this->pdo->commit();
        } catch (PDOException $e) {
            throw new QueryBuilderException("Failed to commit transaction: " . $e->getMessage());
        }
    }

    /**
     * Rolls back a transaction.
     * @return bool
     * @throws QueryBuilderException
     */
    public function rollback(): bool
    {
        try {
            return $this->pdo->rollBack();
        } catch (PDOException $e) {
            throw new QueryBuilderException("Failed to rollback transaction: " . $e->getMessage());
        }
    }

    /**
     * Checks if a transaction is active.
     * @return bool
     */
    public function isInTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }

}
