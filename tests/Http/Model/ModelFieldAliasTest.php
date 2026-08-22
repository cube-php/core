<?php

use Cube\Http\Model;
use Cube\Modules\Db\DBConnection;
use Cube\Modules\Db\DBConnectorItem;
use Cube\Modules\Db\DBTable;

class AliasTransactionModel extends Model
{
    protected static $schema = 'transactions';

    protected static $fields = [
        'reference',
        'created_at',
        'amount',
    ];

    protected static $private_fields = [
        'secret',
    ];

    protected static array $field_aliases = [
        'reference' => 'transaction_ref',
        'created_at' => 'create_date',
        'updated_at' => 'update_date',
        'secret' => 'secret_ref',
    ];

    protected array $cast = [
        'amount' => self::CAST_TYPE_FLOAT,
    ];

    public static function query(): DBTable
    {
        return new DBTable('transactions', makeAliasModelConnection(), static::getFieldAliases());
    }

    public static function databasePayload(array $data): array
    {
        return static::getDatabasePayload($data);
    }
}

function makeAliasModelConnection(): DBConnection
{
    return new DBConnection(
        new DBConnectorItem(
            'test',
            true,
            new PDO('sqlite::memory:'),
            'test',
            'test',
            'utf8',
        )
    );
}

function aliasModelUpdates(Model $model): array
{
    $updates = new ReflectionProperty(Model::class, '_updates');

    return $updates->getValue($model);
}

it('hydrates database columns into model field aliases', function () {
    $model = AliasTransactionModel::wrapData((object) [
        'id' => 7,
        'transaction_ref' => 'trx-001',
        'create_date' => '2026-08-22 10:00:00',
        'amount' => '25.50',
        'secret_ref' => 'hidden',
    ]);

    expect($model->reference)->toBe('trx-001')
        ->and($model->transaction_ref)->toBe('trx-001')
        ->and($model->created_at)->toBe('2026-08-22 10:00:00')
        ->and($model->create_date)->toBe('2026-08-22 10:00:00')
        ->and($model->amount)->toBe(25.5)
        ->and($model->secret)->toBe('hidden')
        ->and($model->secret_ref)->toBe('hidden')
        ->and($model->data())->toBe([
            'id' => 7,
            'reference' => 'trx-001',
            'created_at' => '2026-08-22 10:00:00',
            'amount' => 25.5,
        ]);
});

it('stores updates with model field aliases even when legacy database names are used', function () {
    $model = new AliasTransactionModel();

    $model->transaction_ref = 'trx-002';
    $model->create_date = '2026-08-22 11:00:00';

    expect($model->reference)->toBe('trx-002')
        ->and($model->created_at)->toBe('2026-08-22 11:00:00')
        ->and(aliasModelUpdates($model))->toBe([
            'reference' => 'trx-002',
            'created_at' => '2026-08-22 11:00:00',
        ]);
});

it('converts model payloads to database columns for write operations', function () {
    expect(AliasTransactionModel::databasePayload([
        'reference' => 'trx-003',
        'created_at' => '2026-08-22 12:00:00',
        'amount' => 100,
    ]))->toBe([
        'transaction_ref' => 'trx-003',
        'create_date' => '2026-08-22 12:00:00',
        'amount' => 100,
    ]);
});

it('selects aliased database columns back as model field names', function () {
    $query = AliasTransactionModel::select();

    expect((string) $query)->toBe(
        'SELECT transactions.id, transactions.transaction_ref AS reference, transactions.create_date AS created_at, transactions.amount, transactions.secret_ref AS secret FROM transactions'
    );
});

it('translates model field aliases in fluent select queries', function () {
    $query = AliasTransactionModel::select()
        ->where('reference', 'trx-004')
        ->andLike('created_at', '2026-08')
        ->orderByDesc('created_at')
        ->groupBy('reference');

    expect((string) $query)->toBe(
        'SELECT transactions.id, transactions.transaction_ref AS reference, transactions.create_date AS created_at, transactions.amount, transactions.secret_ref AS secret FROM transactions WHERE transaction_ref = ? AND create_date LIKE ? ORDER BY create_date DESC GROUP BY transaction_ref'
    )
        ->and($query->getSqlParameters())->toBe([
            'trx-004',
            '%2026-08%',
        ]);
});

it('translates explicitly selected model aliases', function () {
    $query = AliasTransactionModel::select('reference', 'created_at');

    expect((string) $query)->toBe(
        'SELECT transactions.transaction_ref AS reference, transactions.create_date AS created_at FROM transactions'
    );
});

it('translates aliases in table-backed update and delete builders', function () {
    $update = AliasTransactionModel::query()
        ->update(['reference' => 'trx-005'])
        ->where('reference', 'trx-004');

    $delete = AliasTransactionModel::query()
        ->delete()
        ->where('reference', 'trx-004');

    expect((string) $update)->toContain('UPDATE transactions SET transaction_ref = ?,update_date = ? WHERE transaction_ref = ?')
        ->and($update->getSqlParameters()[0])->toBe('trx-005')
        ->and($update->getSqlParameters()[2])->toBe('trx-004')
        ->and((string) $delete)->toBe('DELETE FROM transactions WHERE transaction_ref = ?')
        ->and($delete->getSqlParameters())->toBe(['trx-004']);
});
