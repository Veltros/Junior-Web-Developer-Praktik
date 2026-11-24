<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * @mixin \Eloquent
 * @mixin \Illuminate\Database\Eloquent\Builder
 * @property int $id
 * @property string $name
 * @property string|null $description
 */
/**
 * MODEL: Category (Mewakili tabel 'categories' di database)
 * Menyimpan kelompok kategori/genre buku (misal: Fiksi, Sains, Romansa).
 */
class Category extends Model
{
    use HasFactory;

    // Kolom yang boleh diisi
    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * RELASI: 1 Kategori Memiliki Banyak Buku (hasMany)
     * Contoh: Kategori 'Fiksi' memiliki 15 judul buku.
     */
    public function books(): HasMany
    {
        return $this->hasMany(Book::class);
    }

    /**
     * RELASI: 1 Kategori terhubung ke Banyak Item Pesanan melalui Buku (hasManyThrough)
     * Untuk mengetahui berapa kali buku di kategori ini pernah dipesan pelanggan.
     */
    public function orderItems(): HasManyThrough
    {
        return $this->hasManyThrough(OrderItem::class, Book::class);
    }

    /**
     * FUNGSI: Memeriksa apakah kategori aman untuk dihapus.
     * Hanya boleh dihapus jika tidak punya buku dan belum pernah dipesan.
     */
    public function canDelete(): bool
    {
        return $this->books()->count() === 0 && $this->orderItems()->count() === 0;
    }
}
