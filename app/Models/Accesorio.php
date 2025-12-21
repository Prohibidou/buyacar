<?php
namespace App\Models;

use App\Database;

/**
 * Modelo Accesorio - Compatible con G1 Schema
 * 
 * G1 Schema: Accesorios = idAccesorio(PK), nombre, stock, descripcion, habilitado, eliminado, idOferta
 */
class Accesorio extends Model
{
    protected static string $table = 'accesorios';
    protected static string $primaryKey = 'idAccesorio';

    protected array $fillable = [
        'nombre',
        'stock',
        'descripcion',
        'habilitado',
        'eliminado',
        'idOferta'
    ];

    /**
     * Obtener todos los accesorios habilitados
     */
    public static function disponibles(): array
    {
        $sql = "SELECT * FROM accesorios WHERE habilitado = 1 AND eliminado = 0 AND stock > 0";
        $stmt = Database::query($sql);
        return array_map(fn($row) => new self($row), $stmt->fetchAll());
    }

    /**
     * Obtener accesorios disponibles para un modelo específico con sus precios
     */
    public static function porModelo(int $idModelo): array
    {
        $sql = "SELECT a.*, ma.precio 
                FROM accesorios a
                JOIN modelos_accesorios ma ON a.idAccesorio = ma.idAccesorio
                WHERE ma.idModelo = ? AND a.habilitado = 1 AND a.eliminado = 0 AND a.stock > 0";
        $stmt = Database::query($sql, [$idModelo]);
        return $stmt->fetchAll();
    }

    /**
     * Calcular precio total de accesorios seleccionados para un modelo
     */
    public static function calcularPrecioAccesorios(int $idModelo, array $accesorioIds): float
    {
        if (empty($accesorioIds))
            return 0.0;

        $placeholders = implode(',', array_fill(0, count($accesorioIds), '?'));
        $sql = "SELECT SUM(precio) as total 
                FROM modelos_accesorios 
                WHERE idModelo = ? AND idAccesorio IN ($placeholders)";

        $params = array_merge([$idModelo], $accesorioIds);
        $result = Database::queryOne($sql, $params);

        return (float) ($result['total'] ?? 0);
    }

    public static function find($id): ?self
    {
        $row = Database::queryOne("SELECT * FROM accesorios WHERE idAccesorio = ?", [$id]);
        return $row ? new self($row) : null;
    }
}
