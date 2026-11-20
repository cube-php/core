<?php

use Cube\Exceptions\ModelException;
use Cube\Http\Model;
use Cube\Http\Model\RelationConfig;
use Cube\Http\Model\RelationType;
use Cube\Misc\ModelCollection;
use Cube\Modules\Db\DBConnection;
use Cube\Modules\Db\DBConnectorItem;
use Cube\Modules\Db\DBTable;

class RelationTestConnection extends DBConnection
{
    public int $queries = 0;

    /** @var string[] */
    public array $statements = [];

    public function query($sql, array $params = []): PDOStatement
    {
        $this->queries++;
        $this->statements[] = $sql;
        return parent::query($sql, $params);
    }
}

function relationTestConnection(): RelationTestConnection
{
    static $connection;

    if (!$connection) {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $connection = new RelationTestConnection(new DBConnectorItem(
            'relation-test',
            true,
            $pdo,
            'relation-test',
            'relation-test',
            'utf8',
        ));
    }

    return $connection;
}

class RelationAuthorModel extends Model
{
    protected static $schema = 'relation_authors';
    protected static $fields = ['name'];

    public static function query(): DBTable
    {
        return new DBTable(static::$schema, relationTestConnection());
    }
}

class RelationCommentModel extends Model
{
    protected static $schema = 'relation_comments';
    protected static $fields = ['post_id', 'body'];

    public static function query(): DBTable
    {
        return new DBTable(static::$schema, relationTestConnection());
    }
}

class RelationWalletModel extends Model
{
    protected static $schema = 'relation_wallets';
    protected static $primary_key = 'post_id';
    protected static $fields = ['balance'];

    public static function query(): DBTable
    {
        return new DBTable(static::$schema, relationTestConnection());
    }
}

class RelationPostModel extends Model
{
    protected static $schema = 'relation_posts';
    protected static $fields = ['author_id', 'title'];

    protected static $relations = [
        'author' => [
            RelationConfig::Type => RelationType::One,
            RelationConfig::Model => RelationAuthorModel::class,
            RelationConfig::LocalKey => 'author_id',
        ],
        'comments' => [
            'type' => 'many',
            'model' => RelationCommentModel::class,
            'local_key' => 'id',
            'foreign_key' => 'post_id',
        ],
        'wallet' => RelationWalletModel::class,
    ];

    public static function query(): DBTable
    {
        return new DBTable(static::$schema, relationTestConnection());
    }

    public function author()
    {
        return $this->relation('author');
    }

    public function authorBySignature()
    {
        return $this->relation(RelationAuthorModel::class, 'author_id');
    }

    public function comments()
    {
        return $this->relations('comments');
    }

    public function commentsBySignature()
    {
        return $this->relations(RelationCommentModel::class, 'id', 'post_id');
    }

    public function wallet()
    {
        return $this->relation('wallet');
    }
}

class LazyRelationPostModel extends Model
{
    protected static $schema = 'relation_posts';
    protected static $fields = ['author_id', 'title'];

    public static function query(): DBTable
    {
        return new DBTable(static::$schema, relationTestConnection());
    }
}

function resetRelationTables(): void
{
    $connection = relationTestConnection();
    $pdo = $connection->getConnection();
    $pdo->exec('DROP TABLE IF EXISTS relation_wallets');
    $pdo->exec('DROP TABLE IF EXISTS relation_comments');
    $pdo->exec('DROP TABLE IF EXISTS relation_posts');
    $pdo->exec('DROP TABLE IF EXISTS relation_authors');
    $pdo->exec('CREATE TABLE relation_authors (id INTEGER PRIMARY KEY, name TEXT NOT NULL)');
    $pdo->exec('CREATE TABLE relation_posts (id INTEGER PRIMARY KEY, author_id INTEGER NULL, title TEXT NOT NULL)');
    $pdo->exec('CREATE TABLE relation_comments (id INTEGER PRIMARY KEY, post_id INTEGER NOT NULL, body TEXT NOT NULL)');
    $pdo->exec('CREATE TABLE relation_wallets (post_id INTEGER PRIMARY KEY, balance INTEGER NOT NULL)');
    $pdo->exec("INSERT INTO relation_authors (id, name) VALUES (1, 'Ada'), (2, 'Grace')");
    $pdo->exec("INSERT INTO relation_posts (id, author_id, title) VALUES (1, 1, 'First'), (2, 2, 'Second'), (3, NULL, 'Third')");
    $pdo->exec("INSERT INTO relation_comments (id, post_id, body) VALUES (1, 1, 'A'), (2, 1, 'B'), (3, 2, 'C')");
    $pdo->exec('INSERT INTO relation_wallets (post_id, balance) VALUES (1, 500), (2, 750)');
    $connection->queries = 0;
    $connection->statements = [];
}

beforeEach(fn() => resetRelationTables());

it('adds declared one and many relations to the model select query', function () {
    $sql = (string) RelationPostModel::select();

    expect($sql)
        ->toContain('relation_posts.id AS __cube_relation_parent_key')
        ->toContain('LEFT JOIN relation_authors AS __cube_relation_0 ON relation_posts.author_id = __cube_relation_0.id')
        ->toContain('LEFT JOIN relation_comments AS __cube_relation_1 ON relation_posts.id = __cube_relation_1.post_id')
        ->toContain('LEFT JOIN relation_wallets AS __cube_relation_2 ON relation_posts.id = __cube_relation_2.post_id')
        ->toContain('__cube_relation_0.name AS __cube_relation_0__name')
        ->toContain('__cube_relation_1.body AS __cube_relation_1__body');
});

it('hydrates joined relations with one query and deduplicates parents', function () {
    $posts = RelationPostModel::select()->orderByAsc('id')->fetchAll();
    $connection = relationTestConnection();

    expect($connection->queries)->toBe(1)
        ->and($posts)->toBeInstanceOf(ModelCollection::class)
        ->and($posts)->toHaveCount(3)
        ->and($posts[0]->author()->name)->toBe('Ada')
        ->and($posts[0]->authorBySignature())->toBe($posts[0]->author())
        ->and($posts[0]->comments())->toHaveCount(2)
        ->and($posts[0]->commentsBySignature())->toBe($posts[0]->comments())
        ->and($posts[0]->comments()[0]->body)->toBe('A')
        ->and($posts[0]->comments()[1]->body)->toBe('B')
        ->and($posts[0]->wallet()->balance)->toBe(500)
        ->and($posts[2]->author())->toBeNull()
        ->and($posts[2]->comments())->toHaveCount(0)
        ->and($posts[2]->wallet())->toBeNull()
        ->and($connection->queries)->toBe(1);
});

it('keeps lazy relation queries available and caches their result', function () {
    $post = LazyRelationPostModel::find(1);
    $connection = relationTestConnection();

    expect($connection->queries)->toBe(1);
    $author = $post->lazyRelation(RelationAuthorModel::class, 'author_id');

    expect($author->name)->toBe('Ada')
        ->and($connection->queries)->toBe(2)
        ->and($post->lazyRelation(RelationAuthorModel::class, 'author_id'))->toBe($author)
        ->and($connection->queries)->toBe(2)
        ->and(fn() => $post->relation('author'))->toThrow(ModelException::class);
});

it('caches missing lazy relations instead of querying repeatedly', function () {
    $post = LazyRelationPostModel::wrapData((object) [
        'id' => 99,
        'author_id' => 999,
        'title' => 'Missing author',
    ]);
    $connection = relationTestConnection();

    expect($post->lazyRelation(RelationAuthorModel::class, 'author_id'))->toBeNull()
        ->and($connection->queries)->toBe(1)
        ->and($post->lazyRelation(RelationAuthorModel::class, 'author_id'))->toBeNull()
        ->and($connection->queries)->toBe(1);
});

it('slices hydrated parents without truncating their joined children', function () {
    $posts = RelationPostModel::select()->orderByAsc('id')->fetch(0, 1);

    expect($posts)->toHaveCount(1)
        ->and($posts[0]->id)->toBe(1)
        ->and($posts[0]->comments())->toHaveCount(2)
        ->and(relationTestConnection()->queries)->toBe(1);
});
