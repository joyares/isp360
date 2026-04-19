<?php

declare(strict_types=1);

namespace App\Modules\Finance\Services;

use App\Helpers\ispts_ImageHelper;
use InvalidArgumentException;
use PDO;
use Throwable;

class FinanceIspts
{
    public function __construct(
        private PDO $pdo,
        private ?ispts_ImageHelper $imageHelper = null
    ) {
        $this->imageHelper ??= new ispts_ImageHelper();
    }

    /**
     * Record a new expense and related ledger entry in one ACID transaction.
     * To cancel, pass ['action' => 'cancel', 'expense_id' => <id>] so it only sets status = 0.
     */
    public function ispts_record_expense(array $expenseData, ?array $uploadedImage = null): int
    {
        $this->pdo->beginTransaction();

        try {
            if (($expenseData['action'] ?? '') === 'cancel') {
                $expenseId = (int) ($expenseData['expense_id'] ?? 0);
                if ($expenseId <= 0) {
                    throw new InvalidArgumentException('A valid expense_id is required for cancellation.');
                }

                $cancelExpenseStmt = $this->pdo->prepare(
                    'UPDATE finance_expenses
                     SET status = 0, updated_at = NOW()
                     WHERE expense_id = :expense_id AND status = 1'
                );
                $cancelExpenseStmt->execute(['expense_id' => $expenseId]);

                $cancelLedgerStmt = $this->pdo->prepare(
                    'UPDATE finance_ledger
                     SET status = 0, updated_at = NOW()
                     WHERE reference_type = :reference_type
                       AND reference_id = :reference_id
                       AND status = 1'
                );
                $cancelLedgerStmt->execute([
                    'reference_type' => 'expense',
                    'reference_id' => $expenseId,
                ]);

                $this->pdo->commit();
                return $expenseId;
            }

            $categoryId = (int) ($expenseData['category_id'] ?? 0);
            $amount = (float) ($expenseData['amount'] ?? 0);
            $expenseDate = (string) ($expenseData['expense_date'] ?? date('Y-m-d'));
            $note = (string) ($expenseData['note'] ?? '');

            if ($categoryId <= 0 || $amount <= 0) {
                throw new InvalidArgumentException('Valid category_id and amount are required.');
            }

            $receiptFileName = null;
            if (
                is_array($uploadedImage)
                && isset($uploadedImage['tmp_name'], $uploadedImage['error'])
                && (int) $uploadedImage['error'] === UPLOAD_ERR_OK
            ) {
                $receiptFileName = $this->imageHelper?->ispts_compress($uploadedImage);
            }

            $expenseStmt = $this->pdo->prepare(
                'INSERT INTO finance_expenses
                (category_id, amount, expense_date, note, receipt_path, status, created_at, updated_at)
                VALUES
                (:category_id, :amount, :expense_date, :note, :receipt_path, 1, NOW(), NOW())'
            );

            $expenseStmt->execute([
                'category_id' => $categoryId,
                'amount' => $amount,
                'expense_date' => $expenseDate,
                'note' => $note,
                'receipt_path' => $receiptFileName,
            ]);

            $expenseId = (int) $this->pdo->lastInsertId();

            $ledgerStmt = $this->pdo->prepare(
                'INSERT INTO finance_ledger
                (entry_type, reference_type, reference_id, debit_amount, credit_amount, entry_date, narration, status, created_at, updated_at)
                VALUES
                (:entry_type, :reference_type, :reference_id, :debit_amount, :credit_amount, :entry_date, :narration, 1, NOW(), NOW())'
            );

            $ledgerStmt->execute([
                'entry_type' => 'expense',
                'reference_type' => 'expense',
                'reference_id' => $expenseId,
                'debit_amount' => $amount,
                'credit_amount' => 0,
                'entry_date' => $expenseDate,
                'narration' => $note !== '' ? $note : 'Expense recorded',
            ]);

            $this->pdo->commit();

            return $expenseId;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }
}
