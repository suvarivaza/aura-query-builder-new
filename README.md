# AuraQueryBuilderNew

**Usage**

```
use Suvarivaza\AQBN\QueryBuilder;
```

You can get an instance of the QueryBuilder class anywhere using the static getInstance () method.
```
$db = QueryBuilder::getInstance();
```

To do this, simply create a configuration file for connecting to the database at the path /configs/configDb.php (used by default)
Or define the CONFIG_DB_PATH constant in your index.php
Like:
```
define('CONFIG_DB_PATH', '/path/configDb.php');
```

Example config:
```
$config =  [
    'driver' => 'mysql', // Db driver
    'host' => 'localhost',
    'db_name' => 'db_name',
    'db_user' => 'db_user',
    'db_password' => '',
    'charset' => 'utf8', // Optional
    'prefix' => '', // Table prefix, optional
    'options' => [ // PDO constructor options, optional
        PDO::ATTR_TIMEOUT => 5,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ],
];
```

Or you can pass your database connection config to the method getInstance()
Example:
```
$db = QueryBuilder::getInstance($config);
```

Or you can create an object of the QueryBuilder class and pass the PDO connection to the constructor
Like:
```
$db = new QueryBuilder(PDO $pdo, Aura\SqlQuery\QueryFactory new QueryFactory('mysql'));
```
Or of course you can use PHP DI..

Methods:

```
$result = $db
            ->select()
            ->from('posts')
            ->where('id', '=', 1)
            ->getAll('obj');

        $db
            ->insert('posts')
            ->set(['title' => 'post title'])
            ->execute();

        $db
            ->update('posts')
            ->set(['title' => 'new post title'])
            ->where('id', '=', 1)
            ->execute();

        $db
            ->delete('posts')
            ->where('id', '=', 1)
            ->execute();
            

```
