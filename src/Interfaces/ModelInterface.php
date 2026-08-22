<?php

namespace Cube\Interfaces;

use Cube\Modules\Db\DBDelete;
use Cube\Modules\Db\DBSelect;
use Cube\Modules\Db\DBTable;

interface ModelInterface
{
    public static function all(?array $order = null, ?array $opts = null);

    public static function createEntry(array $entry);

    public static function delete(): DBDelete;

    public static function find(int|string $primary_key);

    public static function findAllBy(string $field, mixed $value, ?array $order = null, ?array $params = null);

    public static function findBy(string $field, mixed $value);

    public static function findByPrimaryKey(int|string $primary_key);

    public static function findByPrimaryKeyAndRemove(int|string $primary_key);

    public static function findByPrimaryKeyAndUpdate(int|string $primary_key, array $update);

    public static function findOrFail(int|string $primary_key, callable $failed): ?self;

    public static function findByOrFail(string $field, mixed $value, callable $failed): ?self;

    public static function fromData(string $classname, object $data);

    public static function getCount();

    public static function getCountBy(string $field, mixed $value);

    public static function getCountQuery();

    public static function getFirst(?string $field = null): ?self;

    public static function getLast(?string $field = null): ?self;

    public static function query(): DBTable;

    public static function select(...$args): DBSelect;

    public static function search(string $field, mixed $keyword, ?int $limit = null, ?int $offset = null);

    public static function sum(string $field);

    public function save(): bool;

    public function relation(string $model, string $field, ?string $name = null);

    public function relations(string $model, string $field, ?string $name = null);

    public function data(): array;

    public function remove();
}
