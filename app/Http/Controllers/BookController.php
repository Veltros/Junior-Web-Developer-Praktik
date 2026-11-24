<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Http\Request;

class BookController extends Controller
{
    /**
     * FUNGSI: Menampilkan halaman katalog semua buku untuk pembeli/user.
     * 1. Menerima input pencarian judul, penulis, penerbit, atau kategori jika user mengetik di kotak cari.
     * 2. Menyaring buku berdasarkan kategori yang diklik atau rentang harga yang dipilih.
     * 3. Mengurutkan buku (misal: dari yang terbaru, termurah, atau termahal).
     * 4. Membatasi tampilan 10 buku per halaman (Pagination) agar website tidak lambat.
     */
    public function index(Request $request)
    {
        $search = trim($request->get('q', ''));
        $categoryId = $request->filled('category') ? (int)$request->category : null;
        $priceRange = $request->get('price', '');
        $selectedAuthor = trim($request->get('author', ''));
        $selectedPublisher = trim($request->get('publisher', ''));
        $sort = $request->get('sort', 'newest');

        $categories = Category::withCount('books')->orderBy('id', 'asc')->get();
        $authors = Book::whereNotNull('author')->where('author', '!=', '')->distinct()->orderBy('author')->pluck('author');
        $publishers = Book::whereNotNull('publisher')->where('publisher', '!=', '')->distinct()->orderBy('publisher')->pluck('publisher');

        $query = Book::with('category');

        if (!empty($search)) {
            $words = array_filter(preg_split('/\s+/', $search), fn($w) => mb_strlen(trim($w)) > 0);

            $query->where(function ($q) use ($search, $words) {
                // 1. Pencarian kecocokan frasa langsung
                $q->where('title', 'like', "%{$search}%") 
                  ->orWhere('author', 'like', "%{$search}%")
                  ->orWhere('publisher', 'like', "%{$search}%")
                  ->orWhere('isbn', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('category', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%");
                  });

                // 2. Jika terdiri dari beberapa kata, cocokkan per kata secara fleksibel
                if (count($words) > 1) {
                    $q->orWhere(function ($subQ) use ($words) {
                        foreach ($words as $w) {
                            $subQ->where(function ($wq) use ($w) {
                                $wq->where('title', 'like', "%{$w}%") 
                                   ->orWhere('author', 'like', "%{$w}%")
                                   ->orWhere('publisher', 'like', "%{$w}%")
                                   ->orWhere('description', 'like', "%{$w}%")
                                   ->orWhereHas('category', function ($cq) use ($w) {
                                      $cq->where('name', 'like', "%{$w}%");
                                   });
                            });
                        }
                    });
                }
            });
        }

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        if (!empty($selectedAuthor)) {
            $query->where('author', $selectedAuthor);
        }

        if (!empty($selectedPublisher)) {
            $query->where('publisher', $selectedPublisher);
        }

        if ($priceRange === 'under100') {
            $query->where('price', '<', 100000);
        } elseif ($priceRange === '100to150') {
            $query->whereBetween('price', [100000, 150000]);
        } elseif ($priceRange === 'above150') {
            $query->where('price', '>', 150000);
        }

        switch ($sort) {
            case 'popular':
                $query->orderBy('stock', 'asc')->orderBy('id', 'asc');
                break;
            case 'price_asc':
                $query->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;
            case 'title_asc':
                $query->orderBy('title', 'asc');
                break;
            case 'newest':
            default:
                $query->orderByDesc('id');
                break;
        }

        $books = $query->paginate(10)->withQueryString();

        $hasActiveFilter = !empty($search) || !empty($categoryId) || !empty($priceRange) || !empty($selectedAuthor) || !empty($selectedPublisher) || $sort !== 'newest';

        return view('books.index', compact(
            'books', 'categories', 'authors', 'publishers',
            'search', 'categoryId', 'priceRange', 'selectedAuthor', 'selectedPublisher', 'sort', 'hasActiveFilter'
        ));
    }

    /**
     * FUNGSI: Menampilkan halaman detail lengkap dari 1 buku yang diklik user.
     * 1. Menampilkan detail lengkap buku (sinopsis, stok, harga, penerbit, dll).
     * 2. Mencari sampai 4 buku lain yang kategorinya sama untuk dijadikan rekomendasi di bawahnya ("Buku Terkait").
     */
    public function show(Book $book)
    {
        $book->load('category');
        $relatedBooks = Book::with('category')
            ->where('category_id', $book->category_id)
            ->where('id', '!=', $book->id)
            ->take(4)
            ->get();

        return view('books.show', compact('book', 'relatedBooks'));
    }
}
