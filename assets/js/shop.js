// // Kafkas Boya - Shop JavaScript File
// // Ürün Filtreleme, Sıralama ve Arama İşlemleri

// class ShopFilter {
//     constructor() {
//         this.products = [];
//         this.filteredProducts = [];
//         this.currentFilters = {
//             brands: [],
//             categories: [],
//             volumes: [],
//             colors: [],
//             surfaces: [],
//             priceMin: null,
//             priceMax: null,
//             inStockOnly: false,
//             search: ''
//         };
//         this.currentSort = 'default';
//         this.init();
//     }

//     init() {
//         this.collectProducts();
//         this.initializeFilters();
//         this.initializeSort();
//         this.initializeView();
//         this.initializeSearch();
//         this.parseURLParams();
//         this.applyInitialFilters();
//     }

//     // Ürünleri topla
//     collectProducts() {
//         const productElements = document.querySelectorAll('.product-item');
//         this.products = Array.from(productElements).map(element => ({
//             element: element,
//             id: element.getAttribute('data-id') || element.querySelector('h5').textContent.toLowerCase().replace(/\s+/g, '-'),
//             brand: element.getAttribute('data-brand'),
//             category: element.getAttribute('data-category'),
//             price: parseFloat(element.getAttribute('data-price')),
//             volume: parseFloat(element.getAttribute('data-volume')),
//             color: element.getAttribute('data-color'),
//             surface: element.getAttribute('data-surface'),
//             rating: parseInt(element.getAttribute('data-rating')),
//             name: element.querySelector('h5').textContent,
//             inStock: !element.querySelector('.badge.bg-danger')
//         }));

//         this.filteredProducts = [...this.products];
//     }

//     // Filtre olaylarını başlat
//     initializeFilters() {
//         // Marka filtreleri
//         document.querySelectorAll('.brand-filter').forEach(checkbox => {
//             checkbox.addEventListener('change', () => this.updateBrandFilters());
//         });

//         // Kategori filtreleri
//         document.querySelectorAll('.category-filter').forEach(checkbox => {
//             checkbox.addEventListener('change', () => this.updateCategoryFilters());
//         });

//         // Hacim filtreleri
//         document.querySelectorAll('.volume-filter').forEach(checkbox => {
//             checkbox.addEventListener('change', () => this.updateVolumeFilters());
//         });

//         // Renk filtreleri
//         document.querySelectorAll('.color-filter').forEach(button => {
//             button.addEventListener('click', (e) => this.updateColorFilters(e));
//         });

//         // Yüzey tipi filtreleri
//         document.querySelectorAll('.surface-filter').forEach(checkbox => {
//             checkbox.addEventListener('change', () => this.updateSurfaceFilters());
//         });

//         // Stok durumu filtresi
//         const inStockOnly = document.getElementById('inStockOnly');
//         if (inStockOnly) {
//             inStockOnly.addEventListener('change', () => {
//                 this.currentFilters.inStockOnly = inStockOnly.checked;
//                 this.applyFilters();
//             });
//         }

//         // Fiyat filtresi
//         const priceMin = document.getElementById('priceMin');
//         const priceMax = document.getElementById('priceMax');
//         if (priceMin && priceMax) {
//             priceMin.addEventListener('input', () => this.updatePriceFilter());
//             priceMax.addEventListener('input', () => this.updatePriceFilter());
//         }
//     }

//     // Sıralama olaylarını başlat
//     initializeSort() {
//         const sortSelect = document.getElementById('sortSelect');
//         if (sortSelect) {
//             sortSelect.addEventListener('change', (e) => {
//                 this.currentSort = e.target.value;
//                 this.sortProducts();
//                 this.renderProducts();
//             });
//         }
//     }

//     // Görünüm değiştirme olaylarını başlat
//     initializeView() {
//         const gridView = document.getElementById('gridView');
//         const listView = document.getElementById('listView');

//         if (gridView) {
//             gridView.addEventListener('click', () => this.setGridView());
//         }

//         if (listView) {
//             listView.addEventListener('click', () => this.setListView());
//         }
//     }

//     // Arama olaylarını başlat
//     initializeSearch() {
//         const productSearch = document.getElementById('productSearch');
//         if (productSearch) {
//             let searchTimeout;
//             productSearch.addEventListener('input', (e) => {
//                 clearTimeout(searchTimeout);
//                 searchTimeout = setTimeout(() => {
//                     this.currentFilters.search = e.target.value.toLowerCase();
//                     this.applyFilters();
//                 }, 300);
//             });
//         }

//         const globalSearch = document.getElementById('globalSearch');
//         if (globalSearch) {
//             globalSearch.addEventListener('keypress', (e) => {
//                 if (e.key === 'Enter') {
//                     this.currentFilters.search = e.target.value.toLowerCase();
//                     this.applyFilters();
//                     // Close search modal if open
//                     const modal = document.querySelector('#searchModal.show');
//                     if (modal) {
//                         bootstrap.Modal.getInstance(modal).hide();
//                     }
//                 }
//             });
//         }
//     }

//     // URL parametrelerini ayrıştır
//     parseURLParams() {
//         const urlParams = new URLSearchParams(window.location.search);
        
//         if (urlParams.has('marka')) {
//             const brand = urlParams.get('marka');
//             this.currentFilters.brands = [brand];
//             const brandCheckbox = document.querySelector(`input[value="${brand}"]`);
//             if (brandCheckbox) {
//                 brandCheckbox.checked = true;
//             }
//         }

//         if (urlParams.has('category')) {
//             const category = urlParams.get('category');
//             this.currentFilters.categories = [category];
//             const categoryCheckbox = document.querySelector(`input[value="${category}"]`);
//             if (categoryCheckbox) {
//                 categoryCheckbox.checked = true;
//             }
//         }

//         if (urlParams.has('search')) {
//             this.currentFilters.search = urlParams.get('search').toLowerCase();
//             const searchInput = document.getElementById('productSearch');
//             if (searchInput) {
//                 searchInput.value = this.currentFilters.search;
//             }
//         }
//     }

//     // Başlangıç filtrelerini uygula
//     applyInitialFilters() {
//         this.applyFilters();
//         this.updateActiveFilters();
//     }

//     // Marka filtrelerini güncelle
//     updateBrandFilters() {
//         this.currentFilters.brands = Array.from(document.querySelectorAll('.brand-filter:checked'))
//             .map(checkbox => checkbox.value);
//         this.applyFilters();
//     }

//     // Kategori filtrelerini güncelle
//     updateCategoryFilters() {
//         this.currentFilters.categories = Array.from(document.querySelectorAll('.category-filter:checked'))
//             .map(checkbox => checkbox.value);
//         this.applyFilters();
//     }

//     // Hacim filtrelerini güncelle
//     updateVolumeFilters() {
//         this.currentFilters.volumes = Array.from(document.querySelectorAll('.volume-filter:checked'))
//             .map(checkbox => parseFloat(checkbox.value));
//         this.applyFilters();
//     }

//     // Renk filtrelerini güncelle
//     updateColorFilters(e) {
//         const color = e.target.getAttribute('data-color');
//         e.target.classList.toggle('active');
        
//         if (e.target.classList.contains('active')) {
//             if (!this.currentFilters.colors.includes(color)) {
//                 this.currentFilters.colors.push(color);
//             }
//         } else {
//             this.currentFilters.colors = this.currentFilters.colors.filter(c => c !== color);
//         }
        
//         this.applyFilters();
//     }

//     // Yüzey tipi filtrelerini güncelle
//     updateSurfaceFilters() {
//         this.currentFilters.surfaces = Array.from(document.querySelectorAll('.surface-filter:checked'))
//             .map(checkbox => checkbox.value);
//         this.applyFilters();
//     }

//     // Fiyat filtresini güncelle
//     updatePriceFilter() {
//         const priceMin = document.getElementById('priceMin');
//         const priceMax = document.getElementById('priceMax');
        
//         this.currentFilters.priceMin = priceMin.value ? parseFloat(priceMin.value) : null;
//         this.currentFilters.priceMax = priceMax.value ? parseFloat(priceMax.value) : null;
        
//         this.applyFilters();
//     }

//     // Filtreleri uygula
//     applyFilters() {
//         this.filteredProducts = this.products.filter(product => {
//             // Marka filtresi
//             if (this.currentFilters.brands.length > 0 && 
//                 !this.currentFilters.brands.includes(product.brand)) {
//                 return false;
//             }

//             // Kategori filtresi
//             if (this.currentFilters.categories.length > 0 && 
//                 !this.currentFilters.categories.includes(product.category)) {
//                 return false;
//             }

//             // Hacim filtresi
//             if (this.currentFilters.volumes.length > 0 && 
//                 !this.currentFilters.volumes.includes(product.volume)) {
//                 return false;
//             }

//             // Renk filtresi
//             if (this.currentFilters.colors.length > 0 && 
//                 !this.currentFilters.colors.includes(product.color)) {
//                 return false;
//             }

//             // Yüzey tipi filtresi
//             if (this.currentFilters.surfaces.length > 0 && 
//                 !this.currentFilters.surfaces.includes(product.surface)) {
//                 return false;
//             }

//             // Fiyat filtresi
//             if (this.currentFilters.priceMin && product.price < this.currentFilters.priceMin) {
//                 return false;
//             }
//             if (this.currentFilters.priceMax && product.price > this.currentFilters.priceMax) {
//                 return false;
//             }

//             // Stok filtresi
//             if (this.currentFilters.inStockOnly && !product.inStock) {
//                 return false;
//             }

//             // Arama filtresi
//             if (this.currentFilters.search && 
//                 !product.name.toLowerCase().includes(this.currentFilters.search)) {
//                 return false;
//             }

//             return true;
//         });

//         this.sortProducts();
//         this.renderProducts();
//         this.updateActiveFilters();
//         this.updateProductCount();
//     }

//     // Ürünleri sırala - DÜZELTİLDİ
//     sortProducts() {
//         const productsGrid = document.getElementById('productsGrid');
        
//         switch (this.currentSort) {
//             case 'price-low':
//                 this.filteredProducts.sort((a, b) => a.price - b.price);
//                 break;
//             case 'price-high':
//                 this.filteredProducts.sort((a, b) => b.price - a.price);
//                 break;
//             case 'name':
//                 this.filteredProducts.sort((a, b) => a.name.localeCompare(b.name));
//                 break;
//             case 'rating':
//                 this.filteredProducts.sort((a, b) => b.rating - a.rating);
//                 break;
//             default:
//                 // Varsayılan sıralama - orijinal DOM sırasını koru
//                 const originalOrder = {};
//                 this.products.forEach((product, index) => {
//                     originalOrder[product.id] = index;
//                 });
//                 this.filteredProducts.sort((a, b) => originalOrder[a.id] - originalOrder[b.id]);
//                 break;
//         }
        
//         // DOM'da yeniden sırala
//         this.filteredProducts.forEach(product => {
//             productsGrid.appendChild(product.element);
//         });
//     }

//     // Ürünleri render et
//     renderProducts() {
//         // Tüm ürünleri gizle
//         this.products.forEach(product => {
//             product.element.style.display = 'none';
//         });

//         // Filtrelenmiş ürünleri göster
//         this.filteredProducts.forEach((product, index) => {
//             product.element.style.display = 'block';
//             // Animasyon ekle
//             setTimeout(() => {
//                 product.element.classList.add('fade-in');
//             }, index * 50);
//         });
//     }

//     // Aktif filtreleri göster
//     updateActiveFilters() {
//         const activeFiltersContainer = document.getElementById('activeFilters');
//         const filterTagsContainer = document.getElementById('filterTags');
        
//         if (!activeFiltersContainer || !filterTagsContainer) return;

//         const activeFilters = [];

//         // Marka filtreleri
//         if (this.currentFilters.brands.length > 0) {
//             this.currentFilters.brands.forEach(brand => {
//                 activeFilters.push({
//                     type: 'brand',
//                     value: brand,
//                     label: this.getBrandLabel(brand)
//                 });
//             });
//         }

//         // Kategori filtreleri
//         if (this.currentFilters.categories.length > 0) {
//             this.currentFilters.categories.forEach(category => {
//                 activeFilters.push({
//                     type: 'category',
//                     value: category,
//                     label: this.getCategoryLabel(category)
//                 });
//             });
//         }

//         // Renk filtreleri
//         if (this.currentFilters.colors.length > 0) {
//             this.currentFilters.colors.forEach(color => {
//                 activeFilters.push({
//                     type: 'color',
//                     value: color,
//                     label: this.getColorLabel(color)
//                 });
//             });
//         }

//         // Fiyat filtresi
//         if (this.currentFilters.priceMin || this.currentFilters.priceMax) {
//             let priceLabel = 'Fiyat: ';
//             if (this.currentFilters.priceMin && this.currentFilters.priceMax) {
//                 priceLabel += `₺${this.currentFilters.priceMin} - ₺${this.currentFilters.priceMax}`;
//             } else if (this.currentFilters.priceMin) {
//                 priceLabel += `₺${this.currentFilters.priceMin}+`;
//             } else {
//                 priceLabel += `₺${this.currentFilters.priceMax}-`;
//             }
//             activeFilters.push({
//                 type: 'price',
//                 label: priceLabel
//             });
//         }

//         // Arama filtresi
//         if (this.currentFilters.search) {
//             activeFilters.push({
//                 type: 'search',
//                 label: `Arama: "${this.currentFilters.search}"`
//             });
//         }

//         // Aktif filtre varsa göster
//         if (activeFilters.length > 0) {
//             const filterTagsHTML = activeFilters.map(filter => `
//                 <span class="badge bg-primary me-1 mb-1">
//                     ${filter.label}
//                     <button type="button" class="btn-close btn-close-white ms-2" 
//                             onclick="shopFilter.removeFilter('${filter.type}', '${filter.value || ''}')"
//                             style="font-size: 0.5rem;"></button>
//                 </span>
//             `).join('');

//             filterTagsContainer.innerHTML = filterTagsHTML;
//             activeFiltersContainer.style.display = 'block';
//         } else {
//             activeFiltersContainer.style.display = 'none';
//         }
//     }

//     // Filtre kaldır
//     removeFilter(type, value) {
//         switch (type) {
//             case 'brand':
//                 this.currentFilters.brands = this.currentFilters.brands.filter(b => b !== value);
//                 const brandCheckbox = document.querySelector(`input.brand-filter[value="${value}"]`);
//                 if (brandCheckbox) brandCheckbox.checked = false;
//                 break;
//             case 'category':
//                 this.currentFilters.categories = this.currentFilters.categories.filter(c => c !== value);
//                 const categoryCheckbox = document.querySelector(`input.category-filter[value="${value}"]`);
//                 if (categoryCheckbox) categoryCheckbox.checked = false;
//                 break;
//             case 'color':
//                 this.currentFilters.colors = this.currentFilters.colors.filter(c => c !== value);
//                 const colorButton = document.querySelector(`button.color-filter[data-color="${value}"]`);
//                 if (colorButton) colorButton.classList.remove('active');
//                 break;
//             case 'price':
//                 this.currentFilters.priceMin = null;
//                 this.currentFilters.priceMax = null;
//                 document.getElementById('priceMin').value = '';
//                 document.getElementById('priceMax').value = '';
//                 break;
//             case 'search':
//                 this.currentFilters.search = '';
//                 document.getElementById('productSearch').value = '';
//                 break;
//         }

//         this.applyFilters();
//     }

//     // Ürün sayısını güncelle
//     updateProductCount() {
//         const productCount = document.getElementById('productCount');
//         if (productCount) {
//             productCount.textContent = `${this.filteredProducts.length} ürün gösteriliyor`;
//         }
//     }

//     // Grid görünümü
//     setGridView() {
//         const productsGrid = document.getElementById('productsGrid');
//         if (productsGrid) {
//             productsGrid.className = 'row g-4';
            
//             // Buton durumlarını güncelle
//             document.getElementById('gridView').classList.add('btn-primary');
//             document.getElementById('gridView').classList.remove('btn-outline-primary');
//             document.getElementById('listView').classList.add('btn-outline-primary');
//             document.getElementById('listView').classList.remove('btn-primary');
//         }
//     }

//     // Liste görünümü
//     setListView() {
//         const productsGrid = document.getElementById('productsGrid');
//         if (productsGrid) {
//             productsGrid.className = 'row g-3';
            
//             // Buton durumlarını güncelle
//             document.getElementById('listView').classList.add('btn-primary');
//             document.getElementById('listView').classList.remove('btn-outline-primary');
//             document.getElementById('gridView').classList.add('btn-outline-primary');
//             document.getElementById('gridView').classList.remove('btn-primary');
//         }
//     }

//     // Tüm filtreleri temizle
//     clearAllFilters() {
//         // Checkbox'ları temizle
//         document.querySelectorAll('.brand-filter, .category-filter, .volume-filter, .surface-filter').forEach(checkbox => {
//             checkbox.checked = false;
//         });

//         // Renk butonlarını temizle
//         document.querySelectorAll('.color-filter').forEach(button => {
//             button.classList.remove('active');
//         });

//         // Fiyat aralığını temizle
//         document.getElementById('priceMin').value = '';
//         document.getElementById('priceMax').value = '';

//         // Stok filtresini temizle
//         document.getElementById('inStockOnly').checked = false;

//         // Arama kutusunu temizle
//         document.getElementById('productSearch').value = '';

//         // Filtreleri sıfırla
//         this.currentFilters = {
//             brands: [],
//             categories: [],
//             volumes: [],
//             colors: [],
//             surfaces: [],
//             priceMin: null,
//             priceMax: null,
//             inStockOnly: false,
//             search: ''
//         };

//         this.applyFilters();
//     }

//     // Belirli bir filtreyi temizle
//     clearFilter(type) {
//         switch (type) {
//             case 'brand':
//                 document.querySelectorAll('.brand-filter').forEach(checkbox => checkbox.checked = false);
//                 this.currentFilters.brands = [];
//                 break;
//             case 'category':
//                 document.querySelectorAll('.category-filter').forEach(checkbox => checkbox.checked = false);
//                 this.currentFilters.categories = [];
//                 break;
//             case 'volume':
//                 document.querySelectorAll('.volume-filter').forEach(checkbox => checkbox.checked = false);
//                 this.currentFilters.volumes = [];
//                 break;
//             case 'color':
//                 document.querySelectorAll('.color-filter').forEach(button => button.classList.remove('active'));
//                 this.currentFilters.colors = [];
//                 break;
//             case 'surface':
//                 document.querySelectorAll('.surface-filter').forEach(checkbox => checkbox.checked = false);
//                 this.currentFilters.surfaces = [];
//                 break;
//         }
//         this.applyFilters();
//     }

//     // Fiyat filtresini uygula
//     applyPriceFilter() {
//         this.updatePriceFilter();
//     }

//     // Label yardımcı fonksiyonları
//     getBrandLabel(brand) {
//         const brandLabels = {
//             'polisan': 'Polisan',
//             'filli': 'Filli Boya',
//             'marshall': 'Marshall',
//             'dyo': 'DYO',
//             'permolit': 'Permolit'
//         };
//         return brandLabels[brand] || brand;
//     }

//     getCategoryLabel(category) {
//         const categoryLabels = {
//             'ic-cephe': 'İç Cephe',
//             'dis-cephe': 'Dış Cephe',
//             'vernik': 'Vernik',
//             'astar': 'Astar',
//             'boya-suluboya': 'Suluboya',
//             'boya-yagli-boya': 'Yağlı Boya'
//         };
//         return categoryLabels[category] || category;
//     }

//     getColorLabel(color) {
//         const colorLabels = {
//             'beyaz': 'Beyaz',
//             'siyah': 'Siyah',
//             'kirmizi': 'Kırmızı',
//             'mavi': 'Mavi',
//             'yesil': 'Yeşil',
//             'sari': 'Sarı',
//             'turuncu': 'Turuncu',
//             'mor': 'Mor'
//         };
//         return colorLabels[color] || color;
//     }
// }

// // ShopFilter'ı global olarak tanımla
// let shopFilter;

// // DOM yüklendiğinde başlat
// document.addEventListener('DOMContentLoaded', () => {
//     shopFilter = new ShopFilter();
// });

// // Global fonksiyonlar
// function clearAllFilters() {
//     shopFilter.clearAllFilters();
// }

// function clearFilter(type) {
//     shopFilter.clearFilter(type);
// }

// function applyPriceFilter() {
//     shopFilter.applyPriceFilter();
// }