<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin \Eloquent
 * @mixin \Illuminate\Database\Eloquent\Builder
 * @property int $id
 * @property int $order_id
 * @property int $book_id
 * @property string $book_title
 * @property float $price
 * @property int $quantity
 * @property float $subtotal
 * @property string $formatted_price
 * @property string $formatted_subtotal
 */
/**
 * MODEL: OrderItem (Mewakili tabel 'order_items' di database)
 * Menyimpan rincian tiap buku yang dibeli dalam sebuah pesanan
 * (ID buku, judul buku saat beli, harga satuan, jumlah beli, dan subtotal).
 */
class OrderItem extends Model
{
    use HasFactory;

    // Kolom yang boleh diisi
    protected $fillable = [
        'order_id',
        'book_id',
        'book_title',
        'price',
        'quantity',
        'subtotal',
    ];

    // Konversi tipe data otomatis
    protected function casts(): array
    {
        return [
            'price'    => 'float',
            'quantity' => 'integer',
            'subtotal' => 'float',
        ];
    }

    /**
     * RELASI: Item ini bagian dari 1 Pesanan Induk (belongsTo)
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * RELASI: Item ini terhubung ke Data Buku (belongsTo)
     */
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    /**
     * ACCESSOR: Format harga satuan buku ke Rupiah.
     */
    public function getFormattedPriceAttribute(): string
    {
        return 'Rp ' . number_format($this->price, 0, ',', '.');
    }

    /**
     * ACCESSOR: Format subtotal (harga x jumlah) ke Rupiah.
     */
    public function getFormattedSubtotalAttribute(): string
    {
        return 'Rp ' . number_format($this->subtotal, 0, ',', '.');
    }
}
