<?php

use Cube\Modules\Db\DBConnection;
use Cube\Modules\Db\DBJoin;
use Cube\Modules\Db\DBTable;
use Cube\Modules\Db\DBUpdate;

function joinTestTable(): DBTable
{
    $connection = (new ReflectionClass(DBConnection::class))->newInstanceWithoutConstructor();
    return new DBTable('users', $connection);
}

it('shares every join type across select update and delete', function ($method, $type) {
    $table = joinTestTable();
    foreach ([$table->select(['users.id']), $table->update(['users.active' => 0]), $table->delete()] as $query) {
        $result = $query->$method(fn(DBJoin $join) => $join->table('teams')->on('$.team_id', '=', 'teams.id'));
        expect($result)->toBe($query)
            ->and((string) $query)->toContain(" $type teams ON users.team_id = teams.id");
    }
})->with([
    ['join', 'JOIN'],
    ['innerJoin', 'INNER JOIN'],
    ['leftJoin', 'LEFT JOIN'],
    ['rightJoin', 'RIGHT JOIN'],
    ['crossJoin', 'CROSS JOIN'],
]);

it('places update joins before SET and preserves bound values', function () {
    $query = joinTestTable()->update(['users.active' => 0])->where('teams.id', 12);
    $params = $query->getSqlParameters();
    $query->innerJoin(fn($join) => $join->table('teams')->on('$.team_id', '=', 'teams.id'));
    $sql = 'UPDATE users INNER JOIN teams ON users.team_id = teams.id SET users.active = ?,updated_at = ? WHERE teams.id = ?';
    expect((string) $query)->toBe($sql)
        ->and((string) $query)->toBe($sql)
        ->and($query->getSqlParameters())->toBe($params)
        ->and($params[0])->toBe(0)
        ->and($params[2])->toBe(12);
});

it('accepts joins before update entries and multiple joins', function () {
    $query = (new DBUpdate(joinTestTable()))
        ->join(fn($join) => $join->table('teams')->on('users.team_id', '=', 'teams.id'))
        ->entry(['users.active' => 1])
        ->leftJoin(fn($join) => $join->table('regions')->on('teams.region_id', '=', 'regions.id'));
    expect((string) $query)->toBe('UPDATE users JOIN teams ON users.team_id = teams.id LEFT JOIN regions ON teams.region_id = regions.id SET users.active = ?,updated_at = ?');
});

it('targets only the base table in joined deletes and keeps ordinary deletes unchanged', function () {
    $query = joinTestTable()->delete()->where('teams.active', 0);
    expect((string) $query)->toBe('DELETE FROM users WHERE teams.active = ?');
    $query->join(fn($join) => $join->table('teams')->on('$.team_id', '=', 'teams.id'));
    expect((string) $query)->toBe('DELETE users FROM users JOIN teams ON users.team_id = teams.id WHERE teams.active = ?')
        ->and($query->getSqlParameters())->toBe([0]);
});

it('preserves cross joins without ON and prepended SQL', function () {
    $query = joinTestTable()->delete()->crossJoin(fn($join) => $join->table('teams'));
    $query->prependSql('EXPLAIN', null);
    expect((string) $query)->toBe('EXPLAIN DELETE users FROM users CROSS JOIN teams');
});
