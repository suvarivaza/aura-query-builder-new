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

SELECT
```
$result = $db->select() // empty value equals '*'
            ->from('posts') // table name
            ->where('id', '=', 1)
            ->getAll('obj'); // can by: 'assoc', 'obj', 'both', 'num'. empty value equals 'assoc'

$result = $db->select('id') // column name
            ->from('posts') // table name
            ->where('id', '=', 1)
            ->getOne();     
            
$result = $db->select(['project_id' , 'order_id']) // if you need to get several columns
            ->from('qcomment_projects_orders')
            ->where('order_id', '=', 123)
            ->getValue('project_id'); // if you need to get only one value

```

INSERT
```            
        $db->insert('posts')
            ->set(['title' => 'post title'])
            ->execute();
```

UPDATE
```           
        $db->update('posts')
            ->set(['title' => 'new post title'])
            ->where('id', '=', 1)
            ->execute();
```

DELETE
```    
        $db->delete('posts')
            ->where('id', '=', 1)
            ->execute();
```
