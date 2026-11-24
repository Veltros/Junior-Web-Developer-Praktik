<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin \Eloquent
 * @mixin \Illuminate\Database\Eloquent\Builder
 * @property int $id
 * @property string $order_number
 * @property int $user_id
 * @property string $recipient_name
 * @property string $recipient_phone
 * @property string $shipping_address
 * @property string $payment_method
 * @property string $payment_status
 * @property string $order_status
 * @property float $total_amount
 * @property string|null $notes
 * @property string $formatted_total
 */
/**
 * MODEL: Order (Mewakili tabel 'orders' di database)
 * Menyimpan data induk transaksi pemesanan buku (nomor invoice, alamat tujuan penerima,
 * metode bayar, status pesanan, dan total harga).
 */
class Order extends Model
{
    use HasFactory;

    // Kolom yang boleh diisi
    protected $fillable = [
        'order_number',
        'user_id',
        'recipient_name',
        'recipient_phone',
        'shipping_address',
        'payment_method',
        'payment_status',
        'order_status',
        'total_amount',
        'notes',
    ];

    // Konversi tipe data otomatis
    protected function casts(): array
    {
        return [
            'total_amount' => 'float',
        ];
    }

    /**
     * RELASI: 1 Pesanan Milik 1 User / Pelanggan (belongsTo)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * RELASI: 1 Pesanan Memiliki Banyak Rincian Barang / Buku (hasMany)
     * Terhubung ke tabel 'order_items'.
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * ACCESSOR: Format total bayar ke Rupiah.
     * Contoh: $order->formatted_total -> "Rp 150.000"
     */
    public function getFormattedTotalAttribute(): string
    {
        return 'Rp ' . number_format($this->total_amount, 0, ',', '.');
    }
}
