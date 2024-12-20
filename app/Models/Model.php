<?php
namespace App\Models;

use App\Database;

/**
 * Modelo base con funcionalidades de Eloquent simplificadas
 */
abstract class Model
{
    protected static string $table = '';
    protected static string $primaryKey = 'id';
    protected array $attributes = [];
    protected array $fillable = [];

    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    public function fill(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            $this->attributes[$key] = $value;
        }
        return $this;
    }

    public function __get(string $key)
    {
        return $this->attributes[$key] ?? null;
    }

    public function __set(string $key, $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function toArray(): array
    {
        return $this->attributes;
    }

    // =========================================
    // Query Methods (Eloquent-like)
    // =========================================

    public static function all(): array
    {
        $sql = "SELECT * FROM " . static::$table;
        $stmt = Database::query($sql);
        return array_map(fn($row) => new static($row), $stmt->fetchAll());
    }

    public static function find(int $id): ?self
    {
        $sql = "SELECT * FROM " . static::$table . " WHERE " . static::$primaryKey . " = ?";
        $stmt = Database::query($sql, [$id]);
        $row = $stmt->fetch();
        return $row ? new static($row) : null;
    }

    public static function where(string $column, $value): array
    {
        $sql = "SELECT * FROM " . static::$table . " WHERE {$column} = ?";
        $stmt = Database::query($sql, [$value]);
        return array_map(fn($row) => new static($row), $stmt->fetchAll());
    }

    public static function whereFirst(string $column, $value): ?self
    {
        $sql = "SELECT * FROM " . static::$table . " WHERE {$column} = ? LIMIT 1";
        $stmt = Database::query($sql, [$value]);
        $row = $stmt->fetch();
        return $row ? new static($row) : null;
    }

    public static function create(array $data): self
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $sql = "INSERT INTO " . static::$table . " ({$columns}) VALUES ({$placeholders})";
        Database::query($sql, array_values($data));

        $id = Database::lastInsertId();
        return static::find((int) $id);
    }

    public function save(): bool
    {
        if (isset($this->attributes[static::$primaryKey])) {
            return $this->update();
        }
        return $this->insert();
    }

    protected function insert(): bool
    {
        $data = array_intersect_key($this->attributes, array_flip($this->fillable));
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $sql = "INSERT INTO " . static::$table . " ({$columns}) VALUES ({$placeholders})";
        Database::query($sql, array_values($data));

        $this->attributes[static::$primaryKey] = Database::lastInsertId();
        return true;
    }

    protected function update(): bool
    {
        $data = array_intersect_key($this->attributes, array_flip($this->fillable));
        $data['updated_at'] = date('Y-m-d H:i:s');

        $sets = implode(', ', array_map(fn($k) => "{$k} = ?", array_keys($data)));
        $values = array_values($data);
        $values[] = $this->attributes[static::$primaryKey];

        $sql = "UPDATE " . static::$table . " SET {$sets} WHERE " . static::$primaryKey . " = ?";
        Database::query($sql, $values);
        return true;
    }

    public function delete(): bool
    {
        $sql = "DELETE FROM " . static::$table . " WHERE " . static::$primaryKey . " = ?";
        Database::query($sql, [$this->attributes[static::$primaryKey]]);
        return true;
    }
}
