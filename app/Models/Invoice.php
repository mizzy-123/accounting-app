<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $entity_id
 * @property string|null $project_id
 * @property string|null $client_id
 * @property string $created_by
 * @property string $invoice_number
 * @property array $items
 * @property string $subtotal
 * @property string $discount
 * @property string $total
 * @property string|null $notes
 * @property string $status draft|sent|paid
 * @property Carbon $issued_date
 * @property Carbon|null $due_date
 * @property Carbon|null $paid_at
 */
class Invoice extends Model
{
    use HasUuids;

    protected $fillable = [
        'entity_id',
        'project_id',
        'client_id',
        'created_by',
        'invoice_number',
        'items',
        'subtotal',
        'discount',
        'total',
        'notes',
        'status',
        'issued_date',
        'due_date',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'items' => 'array',
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'issued_date' => 'date',
            'due_date' => 'date',
            'paid_at' => 'datetime',
        ];
    }

    // ──────────────────── Relations ────────────────────

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ──────────────────── Scopes ────────────────────

    public function scopeForEntity($query, Entity|string $entity): mixed
    {
        $id = $entity instanceof Entity ? $entity->id : $entity;

        return $query->where('entity_id', $id);
    }

    /** Invoice yang belum lunas (draft atau sent). */
    public function scopeUnpaid($query): mixed
    {
        return $query->whereIn('status', ['draft', 'sent']);
    }

    /** Invoice yang sudah melewati due_date dan belum paid. */
    public function scopeOverdue($query): mixed
    {
        return $query->whereIn('status', ['draft', 'sent'])
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->toDateString());
    }

    /** Invoice yang due dalam N hari ke depan (dan belum paid). */
    public function scopeDueSoon($query, int $days = 7): mixed
    {
        $today = now()->toDateString();
        $limit = now()->addDays($days)->toDateString();

        return $query->whereIn('status', ['draft', 'sent'])
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [$today, $limit]);
    }

    // ──────────────────── Computed ────────────────────

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isSent(): bool
    {
        return $this->status === 'sent';
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isOverdue(): bool
    {
        if ($this->isPaid() || ! $this->due_date) {
            return false;
        }

        return $this->due_date->toDateString() < now()->toDateString();
    }

    /**
     * Jumlah hari hingga due_date (negatif jika overdue).
     * Null jika tidak ada due_date atau sudah paid.
     */
    public function daysUntilDue(): ?int
    {
        if ($this->isPaid() || ! $this->due_date) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($this->due_date->startOfDay(), false);
    }

    // ──────────────────── Static helpers ────────────────────

    /**
     * Generate nomor invoice berikutnya untuk entity ini.
     * Format: INV-{YYYY}-{NNN} — reset setiap tahun.
     */
    public static function generateNumber(Entity $entity): string
    {
        $year = now()->year;

        $lastNumber = static::where('entity_id', $entity->id)
            ->whereYear('issued_date', $year)
            ->orderByDesc('invoice_number')
            ->value('invoice_number');

        $sequence = 1;
        if ($lastNumber) {
            // Extract sequence dari format INV-YYYY-NNN
            $parts = explode('-', $lastNumber);
            $sequence = ((int) end($parts)) + 1;
        }

        return sprintf('INV-%d-%03d', $year, $sequence);
    }
}
