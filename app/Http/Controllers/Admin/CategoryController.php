<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CategoryController extends Controller
{
    /**
     * FUNGSI: Menampilkan halaman daftar Kategori Buku di Admin.
     * Mengambil seluruh data kategori buku, dihitung berapa jumlah buku di dalamnya,
     * serta menyiapkan form tambah/edit kategori dalam 1 halaman.
     */
    public function index(Request $request)
    {
        $categories = Category::withCount(['books', 'orderItems'])->orderBy('name')->get();
        $editCategory = null;

        if ($request->filled('edit')) {
            $editCategory = Category::find($request->edit);
        }

        return view('admin.categories.index', compact('categories', 'editCategory'));
    }

    /**
     * FUNGSI: Menambah kategori buku baru ke database.
     * 1. Nama kategori wajib diisi dan tidak boleh kembar (Anti-Duplikasi).
     * 2. Sistem memeriksa huruf besar/kecil (Case-Insensitive) agar tidak ada nama yang mirip.
     * 3. Menyimpan kategori baru ke tabel `categories`.
     */
    public function store(Request $request)
    {
        $trimmedName = trim($request->name ?? '');
        $request->merge(['name' => $trimmedName]);

        $request->validate([
            'name'        => [
                'required',
                'string',
                'max:100',
                Rule::unique('categories', 'name'),
            ],
            'description' => 'nullable|string',
        ], [
            'name.required' => 'Nama kategori wajib diisi.',
            'name.unique'   => "Kategori dengan nama '{$trimmedName}' sudah terdaftar dalam sistem.",
        ]);

        // Case-insensitive & Perlindungan terhadap duplikasi yang di-trim
        $duplicateExists = Category::whereRaw('LOWER(TRIM(name)) = ?', [strtolower($trimmedName)])->exists();
        if ($duplicateExists) {
            throw ValidationException::withMessages([
                'name' => "Kategori dengan nama '{$trimmedName}' sudah terdaftar dalam sistem."
            ]);
        }

        Category::create([
            'name'        => $trimmedName,
            'description' => $request->description,
        ]);

        return redirect()->route('admin.categories.index')
            ->with('success', "Kategori '{$trimmedName}' berhasil ditambahkan.");
    }

    /**
     * FUNGSI: Memperbarui nama atau deskripsi kategori yang sudah ada.
     * Mengubah data kategori dan memastikan nama barunya tidak bentrok dengan kategori lain.
     */
    public function update(Request $request, Category $category)
    {
        $trimmedName = trim($request->name ?? '');
        $request->merge(['name' => $trimmedName]);

        $request->validate([
            'name'        => [
                'required',
                'string',
                'max:100',
                Rule::unique('categories', 'name')->ignore($category->id),
            ],
            'description' => 'nullable|string',
        ], [
            'name.required' => 'Nama kategori wajib diisi.',
            'name.unique'   => "Kategori dengan nama '{$trimmedName}' sudah terdaftar dalam sistem.",
        ]);

        // Case-insensitive & Perlindungan terhadap duplikasi yang di-trim
        $duplicateExists = Category::where('id', '!=', $category->id)
            ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($trimmedName)])
            ->exists();

        if ($duplicateExists) {
            throw ValidationException::withMessages([
                'name' => "Kategori dengan nama '{$trimmedName}' sudah terdaftar dalam sistem."
            ]);
        }

        $category->update([
            'name'        => $trimmedName,
            'description' => $request->description,
        ]);

        return redirect()->route('admin.categories.index')
            ->with('success', "Kategori '{$trimmedName}' berhasil diperbarui.");
    }

    /**
     * FUNGSI: Menghapus sebuah kategori buku.
     * Kategori TIDAK BISA dihapus sembarangan jika:
     * 1. Buku di dalamnya pernah dibeli dalam transaksi riwayat pesanan (menjaga integritas data transaksi).
     * 2. Masih ada buku aktif di dalam kategori tersebut (mencegah buku jadi yatim/tanpa kategori).
     */
    public function destroy(Category $category)
    {
        // Proteksi: Kategori tidak dapat dihapus jika ada pesanan user atau masih ada buku
        $orderItemsCount = $category->orderItems()->count();
        if ($orderItemsCount > 0) {
            return redirect()->route('admin.categories.index')
                ->with('danger', "Gagal menghapus! Kategori '{$category->name}' tidak dapat dihapus karena buku di dalamnya memiliki keterkaitan dengan {$orderItemsCount} riwayat transaksi pesanan pengguna.");
        }

        $booksCount = $category->books()->count();
        if ($booksCount > 0) {
            return redirect()->route('admin.categories.index')
                ->with('danger', "Gagal menghapus! Kategori '{$category->name}' tidak dapat dihapus karena masih memuat {$booksCount} buku. Silakan hapus atau pindahkan buku terlebih dahulu.");
        }

        $name = $category->name;
        $category->delete();

        return redirect()->route('admin.categories.index')
            ->with('success', "Kategori '{$name}' berhasil dihapus.");
    }
}
