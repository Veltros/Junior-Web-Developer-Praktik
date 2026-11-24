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
 * @property int $category_id
 * @property string $title
 * @property string $author
 * @property string|null $publisher
 * @property string|null $isbn
 * @property int|null $year
 * @property float $price
 * @property int $stock
 * @property string|null $description
 * @property string|null $cover_image
 */
/**
 * MODEL: Book (Mewakili tabel 'books' di database)
 * Model ini menyimpan semua data buku (judul, penulis, harga, stok, sampul)
 * dan menghubungkannya dengan kategori serta riwayat pesanan.
 */
class Book extends Model
{
    use HasFactory;

    // Kolom yang diizinkan untuk diisi data secara langsung dari form (Mass Assignment Protection)
    protected $fillable = [
        'category_id',
        'title',
        'author',
        'publisher',
        'isbn',
        'year',
        'price',
        'stock',
        'description',
        'cover_image',
    ];

    // Mengubah tipe data kolom database ke tipe data PHP secara otomatis
    protected function casts(): array
    {
        return [
            'price' => 'float',
            'stock' => 'integer',
            'year'  => 'integer',
        ];
    }

    /**
     * RELASI: 1 Buku Milik 1 Kategori (belongsTo)
     * Contoh: Buku 'Bumi Manusia' terhubung ke kategori 'Sastra'.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * RELASI: 1 Buku bisa tercatat di Banyak Transaksi Pembelian (hasMany)
     * Terhubung ke tabel 'order_items'.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * FUNGSI: Memeriksa apakah buku boleh dihapus atau tidak.
     * Buku hanya boleh dihapus jika belum pernah dibeli sama sekali.
     */
    public function canDelete(): bool
    {
        return $this->orderItems()->count() === 0;
    }

    /**
     * ACCESSOR: Format harga otomatis menjadi mata uang Rupiah.
     * Contoh penggunaan di Blade: $book->formatted_price -> Hasil: "Rp 95.000"
     */
    public function getFormattedPriceAttribute(): string
    {
        return 'Rp ' . number_format($this->price, 0, ',', '.');
    }

    /**
     * ACCESSOR: Mendapatkan link URL lengkap gambar sampul buku.
     * Jika file gambar tidak ditemukan, otomatis memakai gambar default.
     */
    public function getCoverUrlAttribute(): string
    {
        if (!empty($this->cover_image) && file_exists(public_path('assets/uploads/' . $this->cover_image))) {
            return asset('assets/uploads/' . $this->cover_image);
        }
        return asset('assets/uploads/default_book.png');
    }
}
