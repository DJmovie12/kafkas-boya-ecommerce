// Kafkas Boya - Main JavaScript File
// Modern E-ticaret Sitesi İçin Gerekli Tüm JavaScript Fonksiyonları

class KafkasBoyaApp {
    constructor() {
        this.cart = JSON.parse(localStorage.getItem('kafkasCart')) || [];
        this.wishlist = JSON.parse(localStorage.getItem('kafkasWishlist')) || [];
        this.init();
    }

    init() {
        this.initializeEventListeners();
        this.initializeAnimations();
        this.initializeCart();
        this.initializeWishlist();
        this.initializeSearch();
        this.initializeScrollEffects();
        this.updateCartCount();
    }

    // Event Listeners
    initializeEventListeners() {
        // Add to cart buttons
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('add-to-cart')) {
                e.preventDefault();
                const productId = e.target.getAttribute('data-product');
                this.addToCart(productId);
            }

            // Add to wishlist buttons
            if (e.target.classList.contains('add-to-wishlist')) {
                e.preventDefault();
                const productId = e.target.getAttribute('data-product');
                this.addToWishlist(productId);
            }

            // Remove from cart
            if (e.target.classList.contains('remove-from-cart')) {
                e.preventDefault();
                const productId = e.target.getAttribute('data-product');
                this.removeFromCart(productId);
            }

            // Remove from wishlist
            if (e.target.classList.contains('remove-from-wishlist')) {
                e.preventDefault();
                const productId = e.target.getAttribute('data-product');
                this.removeFromWishlist(productId);
            }

            // Quantity change buttons
            if (e.target.classList.contains('quantity-increase')) {
                e.preventDefault();
                const productId = e.target.getAttribute('data-product');
                this.updateQuantity(productId, 1);
            }

            if (e.target.classList.contains('quantity-decrease')) {
                e.preventDefault();
                const productId = e.target.getAttribute('data-product');
                this.updateQuantity(productId, -1);
            }

            // Mobile menu toggle
            if (e.target.classList.contains('navbar-toggler')) {
                e.preventDefault();
                this.toggleMobileMenu();
            }

            // Smooth scroll for anchor links
            if (e.target.tagName === 'A' && e.target.getAttribute('href').startsWith('#')) {
                e.preventDefault();
                const targetId = e.target.getAttribute('href').substring(1);
                this.smoothScrollTo(targetId);
            }
        });

        // Form submissions
        document.addEventListener('submit', (e) => {
            if (e.target.classList.contains('contact-form')) {
                e.preventDefault();
                this.handleContactForm(e.target);
            }

            if (e.target.classList.contains('newsletter-form')) {
                e.preventDefault();
                this.handleNewsletterForm(e.target);
            }
        });

        // Window scroll events
        window.addEventListener('scroll', () => {
            this.handleScroll();
        });

        // Window resize events
        window.addEventListener('resize', () => {
            this.handleResize();
        });
    }

    // Animation Initialization
    initializeAnimations() {
        // Initialize AOS (Animate On Scroll)
        if (typeof AOS !== 'undefined') {
            AOS.init({
                duration: 800,
                easing: 'ease-in-out',
                once: true,
                offset: 100
            });
        }

        // Initialize Splide carousel
        if (typeof Splide !== 'undefined') {
            // Featured products carousel
            const featuredProducts = document.getElementById('featured-products');
            if (featuredProducts) {
                new Splide('#featured-products', {
                    type: 'loop',
                    perPage: 3,
                    perMove: 1,
                    gap: '2rem',
                    autoplay: true,
                    interval: 4000,
                    pauseOnHover: true,
                    breakpoints: {
                        768: {
                            perPage: 2,
                            gap: '1rem'
                        },
                        576: {
                            perPage: 1,
                            gap: '1rem'
                        }
                    }
                }).mount();
            }

            // Brand carousel
            const brandCarousel = document.getElementById('brand-carousel');
            if (brandCarousel) {
                new Splide('#brand-carousel', {
                    type: 'loop',
                    perPage: 6,
                    perMove: 1,
                    gap: '1rem',
                    autoplay: true,
                    interval: 3000,
                    arrows: false,
                    pagination: false,
                    breakpoints: {
                        992: { perPage: 4 },
                        768: { perPage: 3 },
                        576: { perPage: 2 }
                    }
                }).mount();
            }
        }

        // Color palette animation
        this.animateColorPalette();
    }

    // Color Palette Animation
    animateColorPalette() {
        const colorSwatches = document.querySelectorAll('.color-swatch');
        if (colorSwatches.length > 0) {
            let delay = 0;
            colorSwatches.forEach((swatch, index) => {
                setTimeout(() => {
                    swatch.style.animation = 'zoomIn 0.6s ease-out forwards';
                }, delay);
                delay += 200;
            });
        }
    }

    // Cart Functions
    initializeCart() {
        this.renderCart();
    }

    addToCart(productId) {
        const product = this.getProductById(productId);
        if (!product) return;

        const existingItem = this.cart.find(item => item.id === productId);
        
        if (existingItem) {
            existingItem.quantity += 1;
        } else {
            this.cart.push({
                id: productId,
                name: product.name,
                price: product.price,
                image: product.image,
                quantity: 1
            });
        }

        this.saveCart();
        this.updateCartCount();
        this.renderCart();
        this.showNotification('Ürün sepete eklendi!', 'success');
    }

    removeFromCart(productId) {
        this.cart = this.cart.filter(item => item.id !== productId);
        this.saveCart();
        this.updateCartCount();
        this.renderCart();
        this.showNotification('Ürün sepetten kaldırıldı!', 'info');
    }

    updateQuantity(productId, change) {
        const item = this.cart.find(item => item.id === productId);
        if (item) {
            item.quantity += change;
            if (item.quantity <= 0) {
                this.removeFromCart(productId);
            } else {
                this.saveCart();
                this.renderCart();
            }
        }
    }

    updateCartCount() {
        const cartCount = document.getElementById('cart-count');
        if (cartCount) {
            const totalItems = this.cart.reduce((sum, item) => sum + item.quantity, 0);
            cartCount.textContent = totalItems;
            cartCount.style.display = totalItems > 0 ? 'block' : 'none';
        }
    }

    renderCart() {
        // Cart rendering logic for cart page
        const cartContainer = document.getElementById('cart-items');
        if (!cartContainer) return;

        if (this.cart.length === 0) {
            cartContainer.innerHTML = `
                <div class="text-center py-5">
                    <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                    <h4>Sepetiniz Boş</h4>
                    <p class="text-muted">Alışverişe devam etmek için ürünleri inceleyin</p>
                    <a href="shop.html" class="btn btn-primary mt-3">Alışverişe Başla</a>
                </div>
            `;
            return;
        }

        const cartHTML = this.cart.map(item => `
            <div class="cart-item d-flex align-items-center p-3 border-bottom">
                <img src="${item.image}" alt="${item.name}" class="img-fluid rounded me-3" style="width: 80px; height: 80px; object-fit: cover;">
                <div class="flex-grow-1">
                    <h6 class="mb-1">${item.name}</h6>
                    <p class="text-muted mb-2">₺${item.price}</p>
                    <div class="d-flex align-items-center">
                        <button class="btn btn-sm btn-outline-secondary quantity-decrease" data-product="${item.id}">-</button>
                        <span class="mx-3">${item.quantity}</span>
                        <button class="btn btn-sm btn-outline-secondary quantity-increase" data-product="${item.id}">+</button>
                    </div>
                </div>
                <div class="text-end">
                    <h6 class="mb-2">₺${(item.price * item.quantity).toFixed(2)}</h6>
                    <button class="btn btn-sm btn-outline-danger remove-from-cart" data-product="${item.id}">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `).join('');

        cartContainer.innerHTML = cartHTML;
    }

    saveCart() {
        localStorage.setItem('kafkasCart', JSON.stringify(this.cart));
    }

    // Wishlist Functions
    initializeWishlist() {
        this.renderWishlist();
    }

    addToWishlist(productId) {
        const product = this.getProductById(productId);
        if (!product) return;

        if (!this.wishlist.find(item => item.id === productId)) {
            this.wishlist.push({
                id: productId,
                name: product.name,
                price: product.price,
                image: product.image
            });
            this.saveWishlist();
            this.showNotification('Ürün favorilere eklendi!', 'success');
        } else {
            this.showNotification('Ürün zaten favorilerde!', 'info');
        }
    }

    removeFromWishlist(productId) {
        this.wishlist = this.wishlist.filter(item => item.id !== productId);
        this.saveWishlist();
        this.renderWishlist();
        this.showNotification('Ürün favorilerden kaldırıldı!', 'info');
    }

    renderWishlist() {
        // Wishlist rendering logic
        const wishlistContainer = document.getElementById('wishlist-items');
        if (!wishlistContainer) return;

        if (this.wishlist.length === 0) {
            wishlistContainer.innerHTML = `
                <div class="text-center py-5">
                    <i class="fas fa-heart fa-3x text-muted mb-3"></i>
                    <h4>Favori Listeniz Boş</h4>
                    <p class="text-muted">Beğendiğiniz ürünleri favorilere ekleyin</p>
                    <a href="shop.html" class="btn btn-primary mt-3">Alışverişe Başla</a>
                </div>
            `;
            return;
        }

        const wishlistHTML = this.wishlist.map(item => `
            <div class="wishlist-item d-flex align-items-center p-3 border-bottom">
                <img src="${item.image}" alt="${item.name}" class="img-fluid rounded me-3" style="width: 80px; height: 80px; object-fit: cover;">
                <div class="flex-grow-1">
                    <h6 class="mb-1">${item.name}</h6>
                    <p class="text-muted mb-2">₺${item.price}</p>
                </div>
                <div class="text-end">
                    <button class="btn btn-sm btn-primary add-to-cart mb-2" data-product="${item.id}">
                        <i class="fas fa-shopping-cart"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger remove-from-wishlist" data-product="${item.id}">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `).join('');

        wishlistContainer.innerHTML = wishlistHTML;
    }

    saveWishlist() {
        localStorage.setItem('kafkasWishlist', JSON.stringify(this.wishlist));
    }

    // Search Functions
    initializeSearch() {
        const searchInput = document.getElementById('productSearch');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                this.performSearch(e.target.value);
            });
        }

        // Global search
        const globalSearch = document.getElementById('globalSearch');
        if (globalSearch) {
            globalSearch.addEventListener('input', (e) => {
                this.performGlobalSearch(e.target.value);
            });
        }
    }

    performSearch(query) {
        const products = document.querySelectorAll('.product-item');
        const lowerQuery = query.toLowerCase();

        products.forEach(product => {
            const productName = product.querySelector('h5').textContent.toLowerCase();
            const productDescription = product.querySelector('p').textContent.toLowerCase();
            
            if (productName.includes(lowerQuery) || productDescription.includes(lowerQuery)) {
                product.style.display = 'block';
                product.classList.add('fade-in');
            } else {
                product.style.display = 'none';
            }
        });

        this.updateProductCount();
    }

    performGlobalSearch(query) {
        if (query.length < 3) return;

        // Simulate search results
        console.log('Searching for:', query);
        // In real application, this would make an API call
    }

    // Scroll Effects
    initializeScrollEffects() {
        // Parallax effect for hero section
        const hero = document.querySelector('.hero-section');
        if (hero) {
            window.addEventListener('scroll', () => {
                const scrolled = window.pageYOffset;
                const rate = scrolled * -0.5;
                hero.style.transform = `translateY(${rate}px)`;
            });
        }

        // Navbar background on scroll
        const navbar = document.querySelector('.navbar');
        if (navbar) {
            window.addEventListener('scroll', () => {
                if (window.scrollY > 50) {
                    navbar.classList.add('bg-white', 'shadow-sm');
                } else {
                    navbar.classList.remove('bg-white', 'shadow-sm');
                }
            });
        }
    }

    handleScroll() {
        // Show/hide back to top button
        const backToTop = document.getElementById('backToTop');
        if (backToTop) {
            if (window.scrollY > 300) {
                backToTop.style.display = 'block';
            } else {
                backToTop.style.display = 'none';
            }
        }
    }

    handleResize() {
        // Handle responsive changes
        if (window.innerWidth > 991) {
            // Close mobile menu on desktop
            const mobileMenu = document.querySelector('.navbar-collapse');
            if (mobileMenu && mobileMenu.classList.contains('show')) {
                mobileMenu.classList.remove('show');
            }
        }
    }

    // Utility Functions
    smoothScrollTo(targetId) {
        const targetElement = document.getElementById(targetId);
        if (targetElement) {
            const offsetTop = targetElement.offsetTop - 80; // Account for fixed navbar
            window.scrollTo({
                top: offsetTop,
                behavior: 'smooth'
            });
        }
    }

    toggleMobileMenu() {
        const mobileMenu = document.querySelector('.navbar-collapse');
        if (mobileMenu) {
            mobileMenu.classList.toggle('show');
        }
    }

    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} position-fixed top-0 end-0 m-3 z-index-3 slide-up`;
        notification.style.minWidth = '300px';
        notification.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="fas fa-${this.getNotificationIcon(type)} me-2"></i>
                <span>${message}</span>
                <button type="button" class="btn-close ms-auto" onclick="this.parentElement.parentElement.remove()"></button>
            </div>
        `;

        document.body.appendChild(notification);

        // Auto remove after 5 seconds
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 5000);
    }

    getNotificationIcon(type) {
        const icons = {
            success: 'check-circle',
            error: 'exclamation-circle',
            warning: 'exclamation-triangle',
            info: 'info-circle'
        };
        return icons[type] || icons.info;
    }

    // Form Handlers
    handleContactForm(form) {
        const formData = new FormData(form);
        const data = Object.fromEntries(formData);
        
        // Simulate form submission
        this.showNotification('Mesajınız başarıyla gönderildi!', 'success');
        form.reset();
    }

    handleNewsletterForm(form) {
        const email = form.querySelector('input[type="email"]').value;
        
        // Simulate newsletter subscription
        this.showNotification('E-bültene abone oldunuz!', 'success');
        form.reset();
    }

    // Product Data (Mock)
    getProductById(id) {
        const products = {
            'polisan-premium': {
                id: 'polisan-premium',
                name: 'Polisan Premium İç Cephe',
                price: 899,
                image: 'https://via.placeholder.com/300x300/007bff/ffffff?text=Polisan'
            },
            'filli-renk-paleti': {
                id: 'filli-renk-paleti',
                name: 'Filli Boya Renk Paleti',
                price: 459,
                image: 'https://via.placeholder.com/300x300/28a745/ffffff?text=Filli'
            },
            'marshall-dis-cephe': {
                id: 'marshall-dis-cephe',
                name: 'Marshall Dış Cephe',
                price: 1299,
                image: 'https://via.placeholder.com/300x300/ffc107/ffffff?text=Marshall'
            },
            'dyo-eko-serisi': {
                id: 'dyo-eko-serisi',
                name: 'DYO Eko Serisi',
                price: 649,
                image: 'https://via.placeholder.com/300x300/dc3545/ffffff?text=DYO'
            }
        };

        return products[id] || null;
    }

    updateProductCount() {
        const productCount = document.getElementById('productCount');
        if (productCount) {
            const visibleProducts = document.querySelectorAll('.product-item[style*="block"], .product-item:not([style*="none"])').length;
            const totalProducts = document.querySelectorAll('.product-item').length;
            productCount.textContent = `${visibleProducts} ürün gösteriliyor (Toplam: ${totalProducts})`;
        }
    }
}

// Initialize the application when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.kafkasApp = new KafkasBoyaApp();
    
    // Splide slider initialization for popular products
    if (typeof Splide !== 'undefined') {
        new Splide('.splide', {
            type: 'slide',
            perPage: 4,
            perMove: 1,
            gap: '1rem',
            pagination: false,
            breakpoints: {
                1200: { perPage: 3 },
                768: { perPage: 2 },
                576: { perPage: 1 }
            }
        }).mount();
    }
});

// Back to top functionality
window.addEventListener('load', () => {
    const backToTopButton = document.createElement('button');
    backToTopButton.id = 'backToTop';
    backToTopButton.className = 'btn btn-primary position-fixed bottom-0 end-0 m-3 rounded-circle d-none';
    backToTopButton.style.width = '50px';
    backToTopButton.style.height = '50px';
    backToTopButton.innerHTML = '<i class="fas fa-arrow-up"></i>';
    backToTopButton.onclick = () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };
    
    document.body.appendChild(backToTopButton);
});

// Export for use in other files
if (typeof module !== 'undefined' && module.exports) {
    module.exports = KafkasBoyaApp;
}