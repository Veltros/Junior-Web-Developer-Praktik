<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin \Eloquent
 * @mixin \Illuminate\Database\Eloquent\Builder
 * @property int $id
 * @property int|null $user_id
 * @property string $name
 * @property string $email
 * @property string $subject
 * @property string $message
 * @property string|null $admin_reply
 * @property \Illuminate\Support\Carbon|null $replied_at
 * @property int|null $admin_id
 * @property string $status
 */
/**
 * MODEL: Message (Mewakili tabel 'messages' di database)
 * Menyimpan percakapan chat / pesan pertanyaan dari pelanggan ke Administrator,
 * serta balasan resmi dari admin.
 */
class Message extends Model
{
    use HasFactory;

    // Kolom yang boleh diisi
    protected $fillable = [
        'user_id',
        'name',
        'email',
        'subject',
        'message',
        'admin_reply',
        'replied_at',
        'admin_id',
        'status',
    ];

    // Konversi tipe data otomatis
    protected function casts(): array
    {
        return [
            'replied_at' => 'datetime',
        ];
    }

    /**
     * RELASI: Pengirim Pesan (Milik 1 User / Pelanggan)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * RELASI: Penjawab Pesan (Milik 1 Admin yang membalas pesan)
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
