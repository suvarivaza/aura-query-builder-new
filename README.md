# AuraQueryBuilderNew

**Usage**

```
$config = ['driver' => 'mysql', // Db driver
'host' => 'localhost',
'db_name' => 'your-database',
'db_user' => 'root',
'db_password' => 'your-password',
'charset' => 'utf8', // Optional
'prefix' => 'cb_', // Table prefix, optional
'options' => [ // PDO constructor options, optional
PDO::ATTR_TIMEOUT => 5,
PDO::ATTR_EMULATE_PREPARES => false,
PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
],
];


use Suvarivaza\AQBN\QueryBuilder;
$db = new QueryBuilder($config);

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
