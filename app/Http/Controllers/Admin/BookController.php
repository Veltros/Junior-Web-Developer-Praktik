<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BookController extends Controller
{
    /**
     * FUNGSI: Menampilkan tabel daftar buku di panel admin.
     * 1. Menampilkan semua buku beserta nama kategorinya dan jumlah penjualan.
     * 2. Mendukung pencarian (judul, penulis, penerbit, ISBN) dan filter kategori.
     * 3. Menampilkan 10 buku per halaman.
     */
    public function index(Request $request)
    {
        $search = trim($request->get('q', ''));
        $catFilter = $request->filled('category') ? (int)$request->category : null;

        $categories = Category::orderBy('name')->get();

        $query = Book::with('category')->withCount('orderItems');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('author', 'like', "%{$search}%")
                  ->orWhere('publisher', 'like', "%{$search}%")
                  ->orWhere('isbn', 'like', "%{$search}%");
            });
        }

        if ($catFilter) {
            $query->where('category_id', $catFilter);
        }

        $books = $query->orderByDesc('id')->paginate(10)->withQueryString();

        return view('admin.books.index', compact('books', 'categories', 'search', 'catFilter'));
    }

    /**
     * FUNGSI: Menampilkan halaman formulir Tambah Buku Baru.
     */
    public function create()
    {
        $categories = Category::orderBy('name')->get();
        return view('admin.books.create', compact('categories'));
    }

    /**
     * FUNGSI: Menyimpan buku baru ke database (dengan fitur Smart Restock).
     * 1. Validasi input: Kategori, judul, penulis, harga, stok, dan berkas gambar sampul.
     * 2. Fitur Smart Restock:
     *    Jika ISBN atau judul & penulis sudah pernah ada di database, sistem tidak membuat buku kembar,
     *    melainkan otomatis menambahkan jumlah stoknya saja (Restock).
     * 3. Jika benar-benar buku baru: upload gambar sampul ke folder `assets/uploads`, lalu simpan data buku baru.
     */
    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'title'       => 'required|string|max:255',
            'author'      => 'required|string|max:150',
            'publisher'   => 'nullable|string|max:150',
            'isbn'        => 'nullable|string|max:30',
            'year'        => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
            'price'       => 'required|numeric|min:0',
            'stock'       => 'required|integer|min:0',
            'description' => 'nullable|string',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ], [
            'category_id.required' => 'Kategori klasifikasi buku wajib dipilih.',
            'category_id.exists'   => 'Kategori yang dipilih tidak valid.',
            'title.required'       => 'Judul buku wajib diisi.',
            'author.required'      => 'Nama penulis / pengarang buku wajib diisi.',
            'price.required'       => 'Harga jual buku wajib diisi.',
            'price.numeric'        => 'Harga buku harus berupa angka.',
            'price.min'            => 'Harga buku tidak boleh kurang dari 0.',
            'stock.required'       => 'Jumlah stok buku wajib diisi.',
            'stock.integer'        => 'Jumlah stok harus berupa bilangan bulat.',
            'stock.min'            => 'Jumlah stok tidak boleh kurang dari 0.',
            'cover_image.image'    => 'Berkas sampul harus berupa berkas gambar.',
            'cover_image.mimes'    => 'Format gambar sampul yang diizinkan: jpeg, png, jpg, webp.',
            'cover_image.max'      => 'Ukuran gambar sampul maksimal 2MB.',
        ]);

        $trimmedTitle = trim($request->title);
        $trimmedAuthor = trim($request->author);
        $trimmedIsbn = $request->isbn ? trim($request->isbn) : null;
        $addedStock = (int)$request->input('stock', 0);

        // 1. Deteksi apakah buku sudah pernah terdaftar (berdasarkan ISBN atau kombinasi Judul & Penulis)
        $existingBook = null;

        if (!empty($trimmedIsbn)) {
            $existingBook = Book::where('isbn', $trimmedIsbn)->first();
        }

        if (!$existingBook) {
            $existingBook = Book::whereRaw('LOWER(TRIM(title)) = ?', [strtolower($trimmedTitle)])
                ->whereRaw('LOWER(TRIM(author)) = ?', [strtolower($trimmedAuthor)])
                ->first();
        }

        // 2. Jika buku sudah terdaftar di sistem:
        if ($existingBook) {
            // Validasi jika ISBN sama persis tapi nama judul dan penulis berbeda jauh (kemungkinan salah ketik ISBN)
            if (!empty($trimmedIsbn) && $existingBook->isbn === $trimmedIsbn && 
                strtolower(trim($existingBook->title)) !== strtolower($trimmedTitle) &&
                strtolower(trim($existingBook->author)) !== strtolower($trimmedAuthor)) {
                return back()->withInput()->withErrors([
                    'isbn' => "Nomor ISBN '{$trimmedIsbn}' sudah digunakan oleh buku lain: '{$existingBook->title}' karya '{$existingBook->author}'."
                ])->with('danger', "Gagal: Konflik nomor ISBN dengan buku yang sudah ada.");
            }

            // LOGIKA PENGGABUNGAN STOK (SMART RESTOCK):
            // Tambahkan HANYA kuantitas stok baru ke buku yang sudah ada (harga dan data lain tetap dipertahankan)
            $oldStock = (int)$existingBook->stock;
            $existingBook->increment('stock', $addedStock);
            $newStock = $oldStock + $addedStock;

            return redirect()->route('admin.books.index')
                ->with('info', "Buku '{$existingBook->title}' sudah terdaftar sebelumnya (ID: #{$existingBook->id}). Stok baru sebanyak {$addedStock} eks berhasil ditambahkan (Stok awal: {$oldStock} eks → Total stok sekarang: {$newStock} eks). Harga dan informasi buku tetap menggunakan data asli.");
        }

        // 3. Jika buku benar-benar baru, simpan sebagai buku baru
        $coverName = 'default_book.png';
        if ($request->hasFile('cover_image')) {
            $file = $request->file('cover_image');
            $coverName = 'book_' . time() . '_' . Str::random(6) . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('assets/uploads'), $coverName);
        }

        $book = Book::create([
            'category_id' => $request->category_id,
            'title'       => $trimmedTitle,
            'author'      => $trimmedAuthor,
            'publisher'   => $request->publisher ? trim($request->publisher) : null,
            'isbn'        => $trimmedIsbn,
            'year'        => $request->year,
            'price'       => $request->price,
            'stock'       => $addedStock,
            'description' => $request->description,
            'cover_image' => $coverName,
        ]);

        return redirect()->route('admin.books.index')
            ->with('success', "Buku baru '{$book->title}' berhasil ditambahkan ke katalog toko (Stok: {$book->stock} eks).");
    }

    /**
     * FUNGSI: Menampilkan halaman formulir Edit Buku.
     */
    public function edit(Book $book)
    {
        $categories = Category::orderBy('name')->get();
        return view('admin.books.edit', compact('book', 'categories'));
    }

    /**
     * FUNGSI: Memperbarui data buku yang ada di database.
     * 1. Validasi input dan pencegahan duplikasi nama/penulis dengan buku lain.
     * 2. Jika admin mengunggah cover baru, gambar lama dihapus dari server (`unlink`) agar tidak membuang ruang harddisk.
     * 3. Menyimpan perubahan data ke tabel `books`.
     */
    public function update(Request $request, Book $book)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'title'       => 'required|string|max:255',
            'author'      => 'required|string|max:150',
            'publisher'   => 'nullable|string|max:150',
            'isbn'        => 'nullable|string|max:30|unique:books,isbn,' . $book->id,
            'year'        => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
            'price'       => 'required|numeric|min:0',
            'stock'       => 'required|integer|min:0',
            'description' => 'nullable|string',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ], [
            'category_id.required' => 'Kategori klasifikasi buku wajib dipilih.',
            'category_id.exists'   => 'Kategori yang dipilih tidak valid.',
            'title.required'       => 'Judul buku wajib diisi.',
            'author.required'      => 'Nama penulis / pengarang buku wajib diisi.',
            'isbn.unique'          => 'Nomor ISBN ini sudah digunakan oleh buku lain di sistem.',
            'price.required'       => 'Harga jual buku wajib diisi.',
            'price.numeric'        => 'Harga buku harus berupa angka.',
            'stock.required'       => 'Jumlah stok buku wajib diisi.',
            'cover_image.image'    => 'Berkas sampul harus berupa gambar.',
            'cover_image.max'      => 'Ukuran berkas gambar maksimal 2MB.',
        ]);

        // Proteksi Duplikasi saat edit: jangan sampai mengubah nama & penulis menjadi sama dengan buku lain
        $trimmedTitle = trim($request->title);
        $trimmedAuthor = trim($request->author);

        $duplicateBook = Book::where('id', '!=', $book->id)
            ->whereRaw('LOWER(TRIM(title)) = ?', [strtolower($trimmedTitle)])
            ->whereRaw('LOWER(TRIM(author)) = ?', [strtolower($trimmedAuthor)])
            ->first();

        if ($duplicateBook) {
            return back()->withInput()->withErrors([
                'title' => "Buku dengan judul '{$trimmedTitle}' karya penulis '{$trimmedAuthor}' sudah digunakan oleh buku lain (ID: #{$duplicateBook->id})."
            ])->with('danger', "Gagal memperbarui: Judul dan penulis sudah terdaftar pada buku lain.");
        }

        $data = [
            'category_id' => $request->category_id,
            'title'       => $trimmedTitle,
            'author'      => $trimmedAuthor,
            'publisher'   => $request->publisher ? trim($request->publisher) : null,
            'isbn'        => $request->isbn ? trim($request->isbn) : null,
            'year'        => $request->year,
            'price'       => $request->price,
            'stock'       => $request->stock,
            'description' => $request->description,
        ];

        if ($request->hasFile('cover_image')) {
            // Hapus gambar lama jika bukan default
            if (!empty($book->cover_image) && $book->cover_image !== 'default_book.png') {
                $oldPath = public_path('assets/uploads/' . $book->cover_image);
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }

            $file = $request->file('cover_image');
            $coverName = 'book_' . time() . '_' . Str::random(6) . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('assets/uploads'), $coverName);
            $data['cover_image'] = $coverName;
        }

        $book->update($data);

        return redirect()->route('admin.books.index')
            ->with('success', "Buku '{$book->title}' berhasil diperbarui.");
    }

    /**
     * FUNGSI: Menghapus sebuah buku dari katalog.
     * Buku TIDAK BISA dihapus jika pernah dibeli oleh pelanggan (ada riwayat di `order_items`),
     * karena data buku harus tetap ada sebagai bukti transaksi arsip pesanan user.
     * Jika belum pernah dibeli: buku dan file gambar sampulnya dihapus permanen.
     */
    public function destroy(Book $book)
    {
        // Proteksi: Buku tidak dapat dihapus jika ada riwayat pesanan user
        $ordersCount = $book->orderItems()->count();
        if ($ordersCount > 0) {
            return redirect()->route('admin.books.index')
                ->with('danger', "Buku '{$book->title}' tidak dapat dihapus karena memiliki relasi dengan {$ordersCount} riwayat pesanan pengguna. Data buku diperlukan sebagai arsip transaksi user.");
        }

        if (!empty($book->cover_image) && $book->cover_image !== 'default_book.png') {
            $oldPath = public_path('assets/uploads/' . $book->cover_image);
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
        }

        $title = $book->title;
        $book->delete();

        return redirect()->route('admin.books.index')
            ->with('success', "Buku '{$title}' berhasil dihapus dari sistem.");
    }
}
