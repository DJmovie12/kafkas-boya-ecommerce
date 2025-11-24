# Kafkas Boya - Laravel & MySQL Entegrasyon Dokümantasyonu

## Proje Hakkında
Bu dokümantasyon, Kafkas Boya e-ticaret web sitesinin Laravel ve MySQL ile backend entegrasyonu için gerekli bilgileri içermektedir.

## Veritabanı Şeması

### Tablolar

#### 1. users (Kullanıcılar)
```sql
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    remember_token VARCHAR(100) NULL,
    role ENUM('admin', 'customer') DEFAULT 'customer',
    phone VARCHAR(20) NULL,
    address TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### 2. categories (Kategoriler)
```sql
CREATE TABLE categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT NULL,
    parent_id BIGINT UNSIGNED NULL,
    image VARCHAR(255) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
);
```

#### 3. brands (Markalar)
```sql
CREATE TABLE brands (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT NULL,
    logo VARCHAR(255) NULL,
    website VARCHAR(255) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### 4. products (Ürünler)
```sql
CREATE TABLE products (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT NULL,
    short_description TEXT NULL,
    price DECIMAL(10,2) NOT NULL,
    compare_price DECIMAL(10,2) NULL,
    cost DECIMAL(10,2) NULL,
    sku VARCHAR(100) UNIQUE NULL,
    barcode VARCHAR(100) NULL,
    category_id BIGINT UNSIGNED NULL,
    brand_id BIGINT UNSIGNED NULL,
    image VARCHAR(255) NULL,
    images JSON NULL,
    stock_quantity INT DEFAULT 0,
    track_stock BOOLEAN DEFAULT TRUE,
    allow_backorder BOOLEAN DEFAULT FALSE,
    weight DECIMAL(8,2) NULL,
    volume DECIMAL(8,2) NULL,
    color VARCHAR(50) NULL,
    surface_type VARCHAR(50) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    is_featured BOOLEAN DEFAULT FALSE,
    meta_title VARCHAR(255) NULL,
    meta_description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE SET NULL
);
```

#### 5. product_variants (Ürün Varyantları)
```sql
CREATE TABLE product_variants (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    sku VARCHAR(100) NULL,
    stock_quantity INT DEFAULT 0,
    image VARCHAR(255) NULL,
    attributes JSON NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);
```

#### 6. orders (Siparişler)
```sql
CREATE TABLE orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(100) UNIQUE NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    customer_email VARCHAR(255) NOT NULL,
    customer_name VARCHAR(255) NOT NULL,
    customer_phone VARCHAR(20) NULL,
    customer_address TEXT NOT NULL,
    shipping_address TEXT NULL,
    billing_address TEXT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    tax_amount DECIMAL(10,2) DEFAULT 0,
    shipping_amount DECIMAL(10,2) DEFAULT 0,
    total_amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'TRY',
    status ENUM('pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded') DEFAULT 'pending',
    payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
    payment_method VARCHAR(50) NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
```

#### 7. order_items (Sipariş Ürünleri)
```sql
CREATE TABLE order_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    product_variant_id BIGINT UNSIGNED NULL,
    product_name VARCHAR(255) NOT NULL,
    product_sku VARCHAR(100) NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (product_variant_id) REFERENCES product_variants(id) ON DELETE SET NULL
);
```

#### 8. cart_items (Sepet Ürünleri)
```sql
CREATE TABLE cart_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    session_id VARCHAR(255) NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    product_variant_id BIGINT UNSIGNED NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (product_variant_id) REFERENCES product_variants(id) ON DELETE CASCADE
);
```

#### 9. reviews (Ürün Yorumları)
```sql
CREATE TABLE reviews (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    customer_name VARCHAR(255) NOT NULL,
    customer_email VARCHAR(255) NOT NULL,
    rating TINYINT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    title VARCHAR(255) NULL,
    comment TEXT NOT NULL,
    is_approved BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
```

#### 10. contacts (İletişim Formları)
```sql
CREATE TABLE contacts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(255) NOT NULL,
    last_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NULL,
    subject VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    is_replied BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### 11. sliders (Ana Sayfa Slider)
```sql
CREATE TABLE sliders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    subtitle VARCHAR(255) NULL,
    description TEXT NULL,
    image VARCHAR(255) NOT NULL,
    link VARCHAR(255) NULL,
    button_text VARCHAR(100) NULL,
    order_column INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### 12. settings (Ayarlar)
```sql
CREATE TABLE settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    key VARCHAR(255) UNIQUE NOT NULL,
    value TEXT NULL,
    type VARCHAR(50) DEFAULT 'string',
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Örnek Veriler

#### Kategoriler
```sql
INSERT INTO categories (name, slug, description) VALUES
('İç Cephe Boyaları', 'ic-cephe-boyalari', 'Ev ve ofis iç mekanları için boyalar'),
('Dış Cephe Boyaları', 'dis-cephe-boyalari', 'Bina dış cepheleri için dayanıklı boyalar'),
('Vernikler', 'vernikler', 'Ahşap ve metal yüzeyler için koruyucu vernikler'),
('Astarlar', 'astarlar', 'Boya öncesi uygulanan astar ürünleri'),
('Suluboya', 'suluboya', 'Sanatsal çalışmalar için suluboya'),
('Yağlı Boya', 'yagli-boya', 'Sanatsal çalışmalar için yağlı boya');
```

#### Markalar
```sql
INSERT INTO brands (name, slug, description) VALUES
('Polisan', 'polisan', 'Türkiye\'nin önde gelen boya markası'),
('Filli Boya', 'filli-boya', 'Geniş renk paleti ile bilinen boya markası'),
('Marshall', 'marshall', 'Dayanıklı ve kaliteli boya üreticisi'),
('DYO', 'dyo', 'Ekonomik ve kaliteli boya çözümleri'),
('Permolit', 'permolit', 'Endüstriyel boya ve vernik üreticisi');
```

#### Admin Kullanıcısı
```sql
INSERT INTO users (name, email, password, role) VALUES
('Admin Kafkas', 'admin@kafkasboya.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
-- Password: password
```

## Laravel Yapılandırması

### Routes (routes/web.php)
```php
<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use Illuminate\Support\Facades\Route;

// Frontend Routes
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/about', [HomeController::class, 'about'])->name('about');
Route::get('/contact', [ContactController::class, 'index'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');

// Product Routes
Route::get('/shop', [ProductController::class, 'index'])->name('shop');
Route::get('/shop/{product}', [ProductController::class, 'show'])->name('product.show');
Route::get('/search', [ProductController::class, 'search'])->name('search');

// Cart Routes
Route::prefix('cart')->group(function () {
    Route::get('/', [CartController::class, 'index'])->name('cart.index');
    Route::post('/add', [CartController::class, 'add'])->name('cart.add');
    Route::post('/update', [CartController::class, 'update'])->name('cart.update');
    Route::post('/remove', [CartController::class, 'remove'])->name('cart.remove');
    Route::post('/clear', [CartController::class, 'clear'])->name('cart.clear');
});

// Order Routes
Route::prefix('order')->middleware('auth')->group(function () {
    Route::get('/checkout', [OrderController::class, 'checkout'])->name('order.checkout');
    Route::post('/place', [OrderController::class, 'place'])->name('order.place');
    Route::get('/{order}', [OrderController::class, 'show'])->name('order.show');
});

// Authentication Routes
Auth::routes();

// Admin Routes
Route::prefix('admin')->middleware('auth', 'admin')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
    
    // Product Management
    Route::resource('products', AdminProductController::class);
    Route::get('/products/{product}/variants', [AdminProductController::class, 'variants'])->name('admin.products.variants');
    
    // Category Management
    Route::resource('categories', CategoryController::class);
    
    // Brand Management
    Route::resource('brands', BrandController::class);
    
    // Order Management
    Route::resource('orders', AdminOrderController::class);
    Route::post('/orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('admin.orders.status');
    
    // Contacts
    Route::get('/contacts', [ContactController::class, 'adminIndex'])->name('admin.contacts');
    Route::get('/contacts/{contact}', [ContactController::class, 'adminShow'])->name('admin.contacts.show');
});
```

### Model İlişkileri

#### User Model
```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $fillable = ['name', 'email', 'password', 'role', 'phone', 'address'];
    
    protected $hidden = ['password', 'remember_token'];
    
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
    
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
    
    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }
    
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
    
    public function isAdmin()
    {
        return $this->role === 'admin';
    }
}
```

#### Product Model
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'short_description', 'price',
        'compare_price', 'cost', 'sku', 'barcode', 'category_id',
        'brand_id', 'image', 'images', 'stock_quantity', 'track_stock',
        'allow_backorder', 'weight', 'volume', 'color', 'surface_type',
        'is_active', 'is_featured', 'meta_title', 'meta_description'
    ];
    
    protected $casts = [
        'images' => 'array',
        'price' => 'decimal:2',
        'compare_price' => 'decimal:2',
        'cost' => 'decimal:2',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'track_stock' => 'boolean',
        'allow_backorder' => 'boolean'
    ];
    
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    
    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }
    
    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }
    
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
    
    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }
    
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
    
    public function getAverageRating()
    {
        return $this->reviews()->where('is_approved', true)->avg('rating') ?? 0;
    }
    
    public function getReviewCount()
    {
        return $this->reviews()->where('is_approved', true)->count();
    }
    
    public function isInStock()
    {
        return $this->track_stock ? $this->stock_quantity > 0 : true;
    }
    
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }
    
    public function scopeInStock($query)
    {
        return $query->where(function ($q) {
            $q->where('track_stock', false)
              ->orWhere(function ($q2) {
                  $q2->where('track_stock', true)
                     ->where('stock_quantity', '>', 0);
              });
        });
    }
}
```

### Controller Örnekleri

#### ProductController
```php
<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['category', 'brand'])->active();
        
        // Brand filter
        if ($request->has('marka')) {
            $brand = Brand::where('slug', $request->marka)->first();
            if ($brand) {
                $query->where('brand_id', $brand->id);
            }
        }
        
        // Category filter
        if ($request->has('category')) {
            $category = Category::where('slug', $request->category)->first();
            if ($category) {
                $query->where('category_id', $category->id);
            }
        }
        
        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        
        // Price filter
        if ($request->has('price_min')) {
            $query->where('price', '>=', $request->price_min);
        }
        
        if ($request->has('price_max')) {
            $query->where('price', '<=', $request->price_max);
        }
        
        // Sorting
        switch ($request->get('sort', 'default')) {
            case 'price-low':
                $query->orderBy('price', 'asc');
                break;
            case 'price-high':
                $query->orderBy('price', 'desc');
                break;
            case 'name':
                $query->orderBy('name', 'asc');
                break;
            case 'rating':
                $query->orderBy('rating', 'desc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
        }
        
        $products = $query->paginate(24);
        $categories = Category::where('is_active', true)->get();
        $brands = Brand::where('is_active', true)->get();
        
        return view('shop', compact('products', 'categories', 'brands'));
    }
    
    public function show(Product $product)
    {
        $product->load(['category', 'brand', 'reviews' => function ($query) {
            $query->where('is_approved', true)->latest();
        }]);
        
        $relatedProducts = Product::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->active()
            ->limit(8)
            ->get();
        
        return view('shop-single', compact('product', 'relatedProducts'));
    }
    
    public function search(Request $request)
    {
        $query = $request->get('q');
        
        $products = Product::with(['category', 'brand'])
            ->active()
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%");
            })
            ->paginate(24);
        
        return view('shop', compact('products', 'query'));
    }
}
```

## Frontend Entegrasyonu

### API Endpoints

#### Ürün Listesi
```javascript
// Tüm ürünleri getir
fetch('/api/products')
    .then(response => response.json())
    .then(data => console.log(data));

// Filtreli ürünler
fetch('/api/products?brand=polisan&category=ic-cephe')
    .then(response => response.json())
    .then(data => console.log(data));
```

#### Sepet İşlemleri
```javascript
// Sepete ürün ekle
fetch('/api/cart/add', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    body: JSON.stringify({
        product_id: 1,
        quantity: 2
    })
});

// Sepeti görüntüle
fetch('/api/cart')
    .then(response => response.json())
    .then(data => console.log(data));
```

### Blade Template Entegrasyonu

#### Ana Sayfa Slider
```blade
@foreach($sliders as $slider)
<div class="hero-slide" style="background-image: url('{{ $slider->image }}')">
    <div class="hero-content">
        <h1>{{ $slider->title }}</h1>
        <p>{{ $slider->description }}</p>
        <a href="{{ $slider->link }}" class="btn btn-primary">{{ $slider->button_text }}</a>
    </div>
</div>
@endforeach
```

#### Ürün Kartı
```blade
<div class="product-card" data-aos="fade-up">
    <div class="product-image">
        <img src="{{ $product->image }}" alt="{{ $product->name }}">
        <div class="product-actions">
            <button class="btn btn-primary add-to-cart" data-product="{{ $product->id }}">
                <i class="fas fa-shopping-cart"></i> Sepete Ekle
            </button>
        </div>
    </div>
    <div class="product-info">
        <h3>{{ $product->name }}</h3>
        <p class="price">₺{{ number_format($product->price, 2) }}</p>
        <div class="rating">
            @for($i = 1; $i <= 5; $i++)
                <i class="fas fa-star {{ $i <= $product->getAverageRating() ? 'text-warning' : 'text-muted' }}"></i>
            @endfor
        </div>
    </div>
</div>
```

## Güvenlik Önlemleri

### 1. CSRF Koruması
```html
<meta name="csrf-token" content="{{ csrf_token() }}">
```

### 2. XSS Koruması
```php
// Blade template'lerde otomatik escaping
{{ $userInput }}

// Raw HTML için (dikkatli kullanın)
{!! $trustedHtml !!}
```

### 3. SQL Injection Koruması
```php
// Eloquent ORM kullanımı
$products = Product::where('category_id', $categoryId)->get();

// Query Builder kullanımı
$products = DB::table('products')
    ->where('category_id', $categoryId)
    ->get();
```

### 4. Validasyon
```php
$request->validate([
    'name' => 'required|string|max:255',
    'email' => 'required|email|unique:users',
    'password' => 'required|string|min:8|confirmed',
]);
```

## Performans Optimizasyonları

### 1. Eager Loading
```php
$products = Product::with(['category', 'brand', 'variants'])->get();
```

### 2. Cache Kullanımı
```php
$products = Cache::remember('featured_products', 3600, function () {
    return Product::featured()->active()->get();
});
```

### 3. Pagination
```php
$products = Product::paginate(24);
```

### 4. Image Optimization
```php
// Intervention Image kütüphanesi
Image::make($image)->resize(300, 300)->save($path);
```

## Deployment

### 1. Environment Ayarları
```env
APP_NAME="Kafkas Boya"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://kafkasboya.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=kafkasboya
DB_USERNAME=root
DB_PASSWORD=

CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=database
```

### 2. Migration ve Seed
```bash
php artisan migrate
php artisan db:seed
```

### 3. Optimization
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Ekstra Özellikler

### 1. Email Notification
```php
// Order confirmation email
Mail::to($order->customer_email)->send(new OrderConfirmation($order));
```

### 2. SMS Integration
```php
// SMS notification
$smsClient = new SmsClient();
$smsClient->send($order->customer_phone, 'Siparişiniz alındı!');
```

### 3. Analytics Integration
```javascript
// Google Analytics Enhanced Ecommerce
gtag('event', 'purchase', {
    transaction_id: '{{ $order->order_number }}',
    value: {{ $order->total_amount }},
    currency: 'TRY',
    items: [
        @foreach($order->items as $item)
        {
            id: '{{ $item->product_sku }}',
            name: '{{ $item->product_name }}',
            category: '{{ $item->product->category->name }}',
            price: {{ $item->unit_price }},
            quantity: {{ $item->quantity }}
        },
        @endforeach
    ]
});
```

## Sonuç

Bu yapı, Kafkas Boya e-ticaret sitesi için modern, ölçeklenebilir ve güvenli bir backend altyapısı sağlar. Laravel'in güçlü özellikleri ile birlikte, genişletilebilir ve sürdürülebilir bir sistem oluşturulmuştur.

### Geliştirme Aşamaları
1. ✅ Frontend tasarım ve geliştirme
2. 📋 Laravel backend yapılandırması (bu dokümantasyon)
3. 🔄 API entegrasyonu
4. 🧪 Test ve optimizasyon
5. 🚀 Canlıya alım

### Önerilen Paketler
- `spatie/laravel-permission` - Rol ve yetki yönetimi
- `intervention/image` - Resim işleme
- `barryvdh/laravel-dompdf` - PDF oluşturma
- `spatie/laravel-backup` - Yedekleme
- `spatie/laravel-sitemap` - XML sitemap
- `laravel/socialite` - Sosyal medya girişi