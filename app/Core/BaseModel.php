<?php

declare(strict_types=1);

namespace App\Core;

use InvalidArgumentException;
use PDO;

class BaseModel
{
    private const ALLOWED_LIMITS = [10, 20, 50];

    public function __construct(protected PDO $pdo)
    {
    }

    /**
     * Paginate active rows (status = 1) from any table.
     *
     * @return array{data: list<array<string, mixed>>, total: int, page: int, limit: int, total_pages: int}
     */
    public function ispts_paginate(string $tableName, int $limit = 10, int $page = 1): array
    {
        if (!in_array($limit, self::ALLOWED_LIMITS, true)) {
            throw new InvalidArgumentException(
                sprintf('Limit must be one of: %s.', implode(', ', self::ALLOWED_LIMITS))
            );
        }

        if ($page < 1) {
            $page = 1;
        }

        $safeName = preg_replace('/[^a-zA-Z0-9_]/', '', $tableName);
        if ($safeName === '') {
            throw new InvalidArgumentException('Invalid table name.');
        }

        $countStmt = $this->pdo->query(
            sprintf('SELECT COUNT(*) FROM `%s` WHERE status = 1', $safeName)
        );
        $total = (int) ($countStmt !== false ? $countStmt->fetchColumn() : 0);

        $totalPages = $total > 0 ? (int) ceil($total / $limit) : 1;

        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $offset = ($page - 1) * $limit;

        $dataStmt = $this->pdo->prepare(
            sprintf('SELECT * FROM `%s` WHERE status = 1 LIMIT :limit OFFSET :offset', $safeName)
        );
        $dataStmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $dataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $dataStmt->execute();

        /** @var list<array<string, mixed>> $data */
        $data = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => $totalPages,
        ];
    }
}
