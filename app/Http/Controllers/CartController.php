<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\CartItem;
use Illuminate\Http\Request;

class CartController extends Controller
{
    /**
     * FUNGSI: Menyelaraskan (sinkronisasi) keranjang belanja antara Session & Database. 
     * Kalau user belanja sebelum login (tersimpan di browser/session), lalu kemudian dia login,
     * fungsi ini otomatis memasukkan buku-buku tadi ke akunnya di database (tabel `cart_items`).
     */
    public static function syncUserCartWithDatabase()
    {
        if (!auth()->check()) {
            return;
        }

        $userId = auth()->id();
        $sessionCart = session()->get('cart', []);

        // 1. Simpan / gabungkan item/data buku dari session ke database
        if (!empty($sessionCart)) {
            foreach ($sessionCart as $bookId => $item) {
                $book = Book::find($bookId);
                if ($book && $book->stock > 0) {
                    $existing = CartItem::where('user_id', $userId)->where('book_id', $bookId)->first();
                    $qtyToSet = $existing ? min($existing->quantity + $item['qty'], $book->stock) : min($item['qty'], $book->stock);
                    
                    if ($qtyToSet > 0) {
                        CartItem::updateOrCreate(
                            ['user_id' => $userId, 'book_id' => $bookId],
                            ['quantity' => $qtyToSet]
                        );
                    }
                }
            }
        }

        // 2. Muat semua data buku di keranjang dari database ke session 
        $dbItems = CartItem::with('book')->where('user_id', $userId)->get();
        $syncedCart = [];
        foreach ($dbItems as $ci) {
            if ($ci->book) {
                $syncedCart[$ci->book_id] = [
                    'id'          => $ci->book->id,
                    'title'       => $ci->book->title,
                    'price'       => (float)$ci->book->price,
                    'cover_image' => $ci->book->cover_image,
                    'qty'         => (int)$ci->quantity,
                ];
            } else {
                // Buku sudah dihapus dari tabel books, bersihkan baris orphan
                $ci->delete();
            }
        }

        session()->put('cart', $syncedCart);
    }

    /**
     * FUNGSI: Menampilkan halaman isi keranjang belanja.
     * 1. Mengambil data semua buku yang sudah dimasukkan user ke keranjang.
     * 2. Mengecek ulang harga resmi dan ketersediaan stok terkini dari database.
     * 3. Memberi tanda peringatan jika ada buku yang tiba-tiba habis stoknya.
     * 4. Menghitung total harga belanjaan.
     */
    public function index()
    {
        // Memastikan keranjang tersimpan dimuat saat user login
        if (auth()->check()) {
            if (empty(session()->get('cart', []))) {
                self::syncUserCartWithDatabase();
            }
        }

        $cart = session()->get('cart', []);
        $totalPrice = 0;
        $hasStockIssue = false;

        if (!empty($cart)) {
            $bookIds = array_keys($cart);
            $books = Book::whereIn('id', $bookIds)->get()->keyBy('id');

            foreach ($cart as $bookId => &$item) {
                $book = $books->get($bookId);
                $currentStock = $book ? (int)$book->stock : 0;
                $item['current_stock'] = $currentStock;

                // Selalu perbarui harga dan judul terkini dari database
                if ($book) {
                    $item['price'] = (float)$book->price;
                    $item['title'] = $book->title;
                    $item['cover_image'] = $book->cover_image;
                }

                if (!$book || $currentStock <= 0) {
                    $item['is_out_of_stock'] = true;
                    $item['stock_shortage'] = false;
                    $hasStockIssue = true;
                } elseif ($item['qty'] > $currentStock) {
                    $item['is_out_of_stock'] = false;
                    $item['stock_shortage'] = true;
                    $hasStockIssue = true;
                } else {
                    $item['is_out_of_stock'] = false;
                    $item['stock_shortage'] = false;
                }

                $totalPrice += $item['price'] * $item['qty'];
            }
            unset($item);
            session()->put('cart', $cart);
        }

        return view('cart.index', compact('cart', 'totalPrice', 'hasStockIssue'));
    }

    /**
     * FUNGSI: Menambahkan buku ke dalam keranjang belanja.
     * 1. Mengecek apakah user sudah login. Jika belum, diarahkan untuk login terlebih dahulu.
     * 2. Mengecek apakah buku yang dipilih masih punya stok.
     * 3. Jika jumlah yang diminta melebihi stok yang ada, sistem akan menolaknya.
     * 4. Menyimpan data buku ke keranjang belanja (baik di Session maupun di tabel `cart_items`).
     */
    public function add(Request $request)
    {
        if (!auth()->check()) {
            session()->put('url.intended', url()->previous());
            return redirect()->route('login')
                ->with('warning', 'Silakan masuk (login) atau daftar akun terlebih dahulu untuk menambahkan buku ke keranjang.');
        }

        $bookId = (int)$request->input('book_id');
        $qty = max(1, (int)$request->input('quantity', 1));

        $book = Book::find($bookId);
        if (!$book) {
            return back()->with('danger', 'Maaf, buku tidak ditemukan atau telah dihapus dari katalog.');
        }

        if ($book->stock <= 0) {
            return back()->with('danger', 'Maaf, persediaan buku ini sedang habis.');
        }

        $cart = session()->get('cart', []);
        $currentQty = isset($cart[$bookId]) ? $cart[$bookId]['qty'] : 0;

        if ($currentQty + $qty > $book->stock) {
            return back()->with('danger', "Gagal menambahkan! Jumlah di keranjang ({$currentQty} eks) sudah mencapai batas stok yang tersedia ({$book->stock} eks).");
        }

        $newQty = $currentQty + $qty;

        $cart[$bookId] = [
            'id'          => $book->id,
            'title'       => $book->title,
            'price'       => (float)$book->price,
            'cover_image' => $book->cover_image,
            'qty'         => $newQty,
        ];

        session()->put('cart', $cart);

        // Simpan persisten ke database agar tidak hilang saat logout
        CartItem::updateOrCreate(
            ['user_id' => auth()->id(), 'book_id' => $bookId],
            ['quantity' => $newQty]
        );

        return back()->with('success', "Buku '{$book->title}' berhasil ditambahkan ke keranjang belanja!");
    }
    
    /**
     * FUNGSI: Mengubah jumlah (kuantitas) buku di dalam keranjang.
     * 1. Dipanggil saat user menambah atau mengurangi angka jumlah buku di keranjang.
     * 2. Jika user memasukkan angka 0 atau negatif, buku otomatis dihapus dari keranjang.
     * 3. Jika jumlah melebihi stok gudang, sistem akan menolak dan memberi peringatan.
     */
    public function update(Request $request)
    {
        $bookId = (int)$request->input('book_id');
        $qty = (int)$request->input('quantity', 1);

        $book = Book::find($bookId);
        $cart = session()->get('cart', []);

        if (isset($cart[$bookId])) {
            if ($qty <= 0 || !$book) {
                unset($cart[$bookId]);
                session()->put('cart', $cart);

                if (auth()->check()) {
                    CartItem::where('user_id', auth()->id())->where('book_id', $bookId)->delete();
                }

                return back()->with('info', "Buku telah dihapus dari keranjang.");
            }

            if ($qty > $book->stock) {
                return back()->with('danger', "Jumlah melebihi stok yang tersedia saat ini ({$book->stock} eks).");
            }

            $cart[$bookId]['qty'] = $qty;
            $cart[$bookId]['price'] = (float)$book->price;
            $cart[$bookId]['title'] = $book->title;
            session()->put('cart', $cart);

            if (auth()->check()) {
                CartItem::updateOrCreate(
                    ['user_id' => auth()->id(), 'book_id' => $bookId],
                    ['quantity' => $qty]
                );
            }

            return back()->with('success', "Keranjang belanja berhasil diperbarui.");
        }

        return back();
    }

    /**
     * FUNGSI: Menghapus 1 baris buku tertentu dari keranjang belanja.
     * 
     * Menghapus buku yang dipilih dari Session browser dan tabel `cart_items` di database.
     */
    public function remove($id)
    {
        $cart = session()->get('cart', []);
        if (isset($cart[$id])) {
            $title = $cart[$id]['title'];
            unset($cart[$id]);
            session()->put('cart', $cart);

            if (auth()->check()) {
                CartItem::where('user_id', auth()->id())->where('book_id', $id)->delete();
            }

            return back()->with('success', "Buku '{$title}' berhasil dihapus dari keranjang.");
        }
        return back();
    }

    /**
     * FUNGSI: Mengosongkan seluruh isi keranjang belanja.
     * 
     * Menghapus semua buku yang ada di keranjang sekaligus (clear all).
     */
    public function clear()
    {
        session()->forget('cart');
        if (auth()->check()) {
            CartItem::where('user_id', auth()->id())->delete();
        }
        return back()->with('info', 'Keranjang belanja telah dikosongkan.');
    }
}
