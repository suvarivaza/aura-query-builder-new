# AuraQueryBuilderNew

**Usage**

```

use Suvarivaza\AQBN\QueryBuilder;

You can get an instance of the QueryBuilder class anywhere using the static getInstance () method
To do this, you need to specify the data for connecting to PDO in the config.php file
Like:

$db = QueryBuilder::getInstance();

Or you can create an object of the QueryBuilder class and pass the PDO connection to the constructor
Like:

$db = new QueryBuilder(PDO $pdo, Aura\SqlQuery\QueryFactory new QueryFactory('mysql'));

Also you can initialize the class using the PHP DI

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
