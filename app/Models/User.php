<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin \Eloquent
 * @mixin \Illuminate\Database\Eloquent\Builder
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $role
 * @property string|null $phone
 * @property string|null $address
 */
/**
 * MODEL: User (Mewakili tabel 'users' di database)
 * Menyimpan akun pengguna (baik pembeli/customer maupun administrator).
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    // Kolom yang boleh diisi
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'address',
        'role',
    ];

    // Kolom rahasia yang disembunyikan saat data diubah jadi array/json
    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Konversi tipe data otomatis (password otomatis di-hash)
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * FUNGSI: Mengecek apakah pengguna ini adalah Administrator.
     * Return: true jika role === 'admin'.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * FUNGSI: Mengecek apakah pengguna ini adalah Pelanggan/User biasa.
     * Return: true jika role === 'user'.
     */
    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    /**
     * RELASI: 1 User memiliki Banyak Riwayat Pesanan (hasMany)
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * RELASI: 1 User memiliki Banyak Pesan Chat (hasMany)
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * RELASI: 1 User memiliki Banyak Item di Keranjang Belanja Database (hasMany)
     */
    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }
}
