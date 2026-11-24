<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin \Eloquent
 * @mixin \Illuminate\Database\Eloquent\Builder
 * @property int $id
 * @property int $user_id
 * @property int $book_id
 * @property int $quantity
 * @property Book|null $book
 * @property User|null $user
 */
/**
 * MODEL: CartItem (Mewakili tabel 'cart_items' di database)
 * Menyimpan data barang di keranjang belanja user secara permanen di database,
 * sehingga saat user logout atau ganti perangkat, keranjangnya tidak hilang.
 */
class CartItem extends Model
{
    use HasFactory;

    // Kolom yang boleh diisi
    protected $fillable = [
        'user_id',
        'book_id',
        'quantity',
    ];

    /**
     * RELASI: Barang keranjang ini milik 1 User (belongsTo)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * RELASI: Barang keranjang ini menunjuk ke 1 Buku (belongsTo)
     */
    public function book()
    {
        return $this->belongsTo(Book::class);
    }
}
