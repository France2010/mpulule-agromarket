<?php
// welcome.php - Landing page with dynamic slideshow
session_start();
require_once __DIR__ . '/config.php';

// If already logged in, redirect
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_role'] === 'admin') {
        header('Location: /agro_project/admin/index.php');
    } else {
        header('Location: /agro_project/index.php');
    }
    exit;
}

$page_title = 'Welcome';
$site_name = get_setting('site_name');
$tagline = get_setting('site_tagline');

// ========== SLIDE IMAGES FROM YOUR PRODUCTS FOLDER ==========
$slides = [
    [
        'image' => '/agro_project/uploads/products/t1.jpg',
        'title' => '🌾 Quality Agricultural Inputs',
        'desc' => 'Certified seeds, fertilizers, and equipment for every farm'
    ],
    [
        'image' => '/agro_project/uploads/products/t2.jpg',
        'title' => '🚜 Modern Farming Equipment',
        'desc' => 'Tractors, irrigation systems, and post-harvest machinery'
    ],
    [
        'image' => '/agro_project/uploads/products/t3.jpg',
        'title' => '🌿 Sustainable Agriculture',
        'desc' => 'Eco-friendly solutions for better crop yields'
    ],
    [
        'image' => '/agro_project/uploads/products/t4.jpg',
        'title' => '💰 Agricultural Financing',
        'desc' => 'Flexible loans tailored to your farm size'
    ],
    [
        'image' => '/agro_project/uploads/products/t5.jpg',
        'title' => '🌍 Nationwide Delivery',
        'desc' => 'Fast and reliable delivery across all regions of Tanzania'
    ],
    [
        'image' => '/agro_project/uploads/products/t6.jpg',
        'title' => '🌱 Quality Seeds',
        'desc' => 'High-yield hybrid and open-pollinated varieties'
    ],
    [
        'image' => '/agro_project/uploads/products/t7.jpg',
        'title' => '🧪 Fertilizers & Nutrients',
        'desc' => 'Essential nutrients for healthy crop growth'
    ],
    [
        'image' => '/agro_project/uploads/products/t8.jpg',
        'title' => '🤝 Expert Support',
        'desc' => 'We\'re here to help you succeed'
    ]
];

// Check which images actually exist
$existing_slides = [];
foreach ($slides as $slide) {
    $full_path = $_SERVER['DOCUMENT_ROOT'] . $slide['image'];
    if (file_exists($full_path)) {
        $existing_slides[] = $slide;
    }
}

// If no images exist, use fallback slides
if (empty($existing_slides)) {
    $existing_slides = [
        [
            'image' => '',
            'title' => '🌾 Welcome to Mpulule AgroMarket',
            'desc' => 'Tanzania\'s premier agricultural marketplace'
        ],
        [
            'image' => '',
            'title' => '🚜 Quality Products',
            'desc' => 'Find everything you need for your farm'
        ],
        [
            'image' => '',
            'title' => '💰 Agricultural Loans',
            'desc' => 'Get financing for your farm'
        ]
    ];
}

require_once __DIR__ . '/includes/header.php';
?>

<style>
/* ========== WELCOME PAGE STYLES ========== */
.welcome-hero {
    min-height: 100vh;
    background: linear-gradient(135deg, #0a0f1c 0%, #0c1122 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
    position: relative;
    overflow: hidden;
}

.welcome-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background: 
        radial-gradient(ellipse at 20% 50%, rgba(245, 158, 11, 0.08) 0%, transparent 60%),
        radial-gradient(ellipse at 80% 50%, rgba(245, 158, 11, 0.05) 0%, transparent 60%);
}

.welcome-content {
    max-width: 1200px;
    width: 100%;
    position: relative;
    z-index: 1;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 50px;
    align-items: center;
}

/* ========== LEFT SIDE - SLIDESHOW ========== */
.slideshow-container {
    position: relative;
    border-radius: 24px;
    overflow: hidden;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
    border: 1px solid rgba(245, 158, 11, 0.15);
    height: 450px;
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
}

.slide {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    transition: opacity 1.5s ease-in-out;
    z-index: 1;
    display: flex;
    align-items: center;
    justify-content: center;
}

.slide.active {
    opacity: 1;
    z-index: 2;
}

.slide img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.slide-fallback {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    color: #f59e0b;
    font-size: 5rem;
    padding: 20px;
    text-align: center;
}

.slide-fallback span {
    font-size: 1.5rem;
    color: #e5e7eb;
    margin-top: 16px;
    font-weight: 600;
}

.slide-fallback small {
    font-size: 0.9rem;
    color: #9ca3af;
    margin-top: 8px;
}

.slide-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 30px 24px;
    background: linear-gradient(transparent, rgba(0, 0, 0, 0.85));
    z-index: 3;
}

.slide-overlay h3 {
    color: #fbbf24;
    font-size: 1.3rem;
    font-weight: 700;
    margin-bottom: 4px;
}

.slide-overlay p {
    color: #9ca3af;
    font-size: 13px;
}

/* Slide indicators */
.slide-indicators {
    position: absolute;
    bottom: 16px;
    right: 24px;
    display: flex;
    gap: 8px;
    z-index: 5;
}

.slide-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.3);
    cursor: pointer;
    transition: all 0.3s;
    border: none;
    padding: 0;
}

.slide-dot.active {
    background: #f59e0b;
    width: 30px;
    border-radius: 5px;
}

.slide-dot:hover {
    background: rgba(255, 255, 255, 0.6);
}

/* Slide badge */
.slide-badge {
    position: absolute;
    top: 20px;
    left: 20px;
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: white;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
    z-index: 4;
}

/* Slide controls */
.slide-control {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(0, 0, 0, 0.5);
    color: white;
    border: none;
    padding: 12px 16px;
    cursor: pointer;
    z-index: 4;
    font-size: 18px;
    transition: all 0.3s;
    border-radius: 8px;
}

.slide-control:hover {
    background: rgba(245, 158, 11, 0.6);
}

.slide-control.prev {
    left: 12px;
}

.slide-control.next {
    right: 12px;
}

/* ========== RIGHT SIDE - BRANDING ========== */
.brand-section {
    padding: 20px 0;
}

.brand-section .logo-icon {
    font-size: 3rem;
    color: #f59e0b;
    margin-bottom: 16px;
    display: block;
}

.brand-section h1 {
    font-size: 3.5rem;
    font-weight: 900;
    line-height: 1.1;
    margin-bottom: 16px;
}

.brand-section h1 .highlight {
    background: linear-gradient(135deg, #fbbf24, #f59e0b);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
}

.brand-section .tagline {
    font-size: 1.15rem;
    color: #9ca3af;
    margin-bottom: 32px;
    max-width: 500px;
    line-height: 1.6;
}

.brand-section .features {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-bottom: 32px;
}

.feature-item {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #e5e7eb;
    font-size: 13px;
    padding: 8px 12px;
    background: rgba(30, 41, 59, 0.5);
    border-radius: 10px;
    border: 1px solid rgba(245, 158, 11, 0.08);
    transition: all 0.3s;
}

.feature-item:hover {
    background: rgba(30, 41, 59, 0.8);
    border-color: rgba(245, 158, 11, 0.2);
    transform: translateX(5px);
}

.feature-item i {
    color: #f59e0b;
    font-size: 16px;
    width: 20px;
    text-align: center;
}

/* ========== ACTION BUTTONS ========== */
.action-buttons {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
}

.btn-welcome-primary {
    padding: 14px 36px;
    background: linear-gradient(135deg, #f59e0b, #d97706);
    border: none;
    border-radius: 12px;
    color: white;
    font-weight: 700;
    font-size: 16px;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 10px;
}

.btn-welcome-primary:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 30px rgba(245, 158, 11, 0.4);
}

.btn-welcome-secondary {
    padding: 14px 36px;
    background: transparent;
    border: 1.5px solid rgba(245, 158, 11, 0.4);
    border-radius: 12px;
    color: #fbbf24;
    font-weight: 600;
    font-size: 16px;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 10px;
}

.btn-welcome-secondary:hover {
    background: rgba(245, 158, 11, 0.1);
    border-color: #f59e0b;
    transform: translateY(-3px);
}

/* ========== STATS ========== */
.stats-bar {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-top: 32px;
    padding-top: 24px;
    border-top: 1px solid rgba(245, 158, 11, 0.1);
}

.stat-item {
    text-align: center;
}

.stat-item .number {
    font-size: 1.5rem;
    font-weight: 800;
    background: linear-gradient(135deg, #fbbf24, #f59e0b);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
}

.stat-item .label {
    font-size: 11px;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-top: 2px;
}

/* ========== RESPONSIVE ========== */
@media (max-width: 1024px) {
    .welcome-content {
        grid-template-columns: 1fr;
        gap: 32px;
        text-align: center;
    }
    
    .brand-section h1 {
        font-size: 2.8rem;
    }
    
    .brand-section .tagline {
        margin: 0 auto 24px;
        max-width: 100%;
    }
    
    .brand-section .features {
        justify-items: center;
        max-width: 500px;
        margin: 0 auto 24px;
    }
    
    .action-buttons {
        justify-content: center;
    }
    
    .stats-bar {
        max-width: 500px;
        margin: 24px auto 0;
    }
    
    .slideshow-container {
        height: 350px;
        max-width: 600px;
        margin: 0 auto;
    }
}

@media (max-width: 640px) {
    .brand-section h1 {
        font-size: 2rem;
    }
    
    .brand-section .features {
        grid-template-columns: 1fr;
    }
    
    .action-buttons {
        flex-direction: column;
        align-items: center;
    }
    
    .btn-welcome-primary,
    .btn-welcome-secondary {
        width: 100%;
        max-width: 300px;
        justify-content: center;
    }
    
    .stats-bar {
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }
    
    .slideshow-container {
        height: 250px;
    }
    
    .slide-overlay {
        padding: 16px 16px;
    }
    
    .slide-overlay h3 {
        font-size: 1rem;
    }
    
    .slide-control {
        padding: 8px 12px;
        font-size: 14px;
    }
}
</style>

<!-- ========== WELCOME HERO ========== -->
<div class="welcome-hero">
    <div class="welcome-content">
        
        <!-- LEFT: Slideshow -->
        <div class="slideshow-container" id="slideshow">
            <!-- Slides will be inserted by JavaScript -->
            
            <!-- Badge -->
            <div class="slide-badge">
                <i class="fas fa-star"></i> Trusted Marketplace
            </div>
            
            <!-- Controls -->
            <button class="slide-control prev" onclick="changeSlide(-1)">
                <i class="fas fa-chevron-left"></i>
            </button>
            <button class="slide-control next" onclick="changeSlide(1)">
                <i class="fas fa-chevron-right"></i>
            </button>
            
            <!-- Indicators -->
            <div class="slide-indicators" id="indicators"></div>
        </div>

        <!-- RIGHT: Branding -->
        <div class="brand-section">
            <i class="fas fa-leaf logo-icon"></i>
            <h1>
                Welcome to <span class="highlight">Mpulule AgroMarket</span>
            </h1>
            <p class="tagline">
                <?php echo e($tagline ?: 'Tanzania\'s premier agricultural marketplace connecting farmers and agribusinesses with quality crops, fertilizers, equipment, and financing.'); ?>
            </p>
            
            <div class="features">
                <div class="feature-item">
                    <i class="fas fa-seedling"></i>
                    <span>Quality Products</span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-truck"></i>
                    <span>Nationwide Delivery</span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-hand-holding-dollar"></i>
                    <span>Agricultural Loans</span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-headset"></i>
                    <span>Expert Support</span>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <a href="/agro_project/login.php" class="btn-welcome-primary">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </a>
                <a href="/agro_project/register.php" class="btn-welcome-secondary">
                    <i class="fas fa-user-plus"></i> Create Account
                </a>
            </div>

            <!-- Stats -->
            <div class="stats-bar">
                <?php 
                $total_products = (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM products WHERE active=1"))['c'];
                $total_users = (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM users"))['c'];
                $total_orders = (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM orders"))['c'];
                $total_categories = (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM categories"))['c'];
                ?>
                <div class="stat-item">
                    <div class="number"><?php echo number_format($total_products); ?>+</div>
                    <div class="label">Products</div>
                </div>
                <div class="stat-item">
                    <div class="number"><?php echo number_format($total_users); ?>+</div>
                    <div class="label">Users</div>
                </div>
                <div class="stat-item">
                    <div class="number"><?php echo number_format($total_orders); ?>+</div>
                    <div class="label">Orders</div>
                </div>
                <div class="stat-item">
                    <div class="number"><?php echo number_format($total_categories); ?></div>
                    <div class="label">Categories</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// ========== SLIDESHOW CONFIGURATION ==========
const slidesData = <?php echo json_encode($existing_slides); ?>;

let currentSlide = 0;
let slideInterval;
const slideDuration = 4000; // 4 seconds per slide

function initSlideshow() {
    const container = document.getElementById('slideshow');
    const indicators = document.getElementById('indicators');
    
    // Clear existing slides
    const existingSlides = container.querySelectorAll('.slide');
    existingSlides.forEach(s => s.remove());
    
    // Create slides
    slidesData.forEach((slide, index) => {
        const slideDiv = document.createElement('div');
        slideDiv.className = 'slide' + (index === 0 ? ' active' : '');
        
        if (slide.image && slide.image !== '') {
            slideDiv.innerHTML = `
                <img src="${slide.image}" alt="${slide.title}" 
                     onerror="this.style.display='none'; this.parentElement.innerHTML = \`
                        <div class=\\"slide-fallback\\">
                            <div>🌾</div>
                            <span>${slide.title}</span>
                            <small>${slide.desc}</small>
                        </div>\`;">
                <div class="slide-overlay">
                    <h3>${slide.title}</h3>
                    <p>${slide.desc}</p>
                </div>
            `;
        } else {
            slideDiv.innerHTML = `
                <div class="slide-fallback">
                    <div>🌾</div>
                    <span>${slide.title}</span>
                    <small>${slide.desc}</small>
                </div>
            `;
        }
        container.appendChild(slideDiv);
    });
    
    // Create indicators
    indicators.innerHTML = '';
    slidesData.forEach((_, index) => {
        const dot = document.createElement('button');
        dot.className = 'slide-dot' + (index === 0 ? ' active' : '');
        dot.setAttribute('data-index', index);
        dot.addEventListener('click', function() {
            goToSlide(parseInt(this.getAttribute('data-index')));
        });
        indicators.appendChild(dot);
    });
}

function goToSlide(index) {
    const slides = document.querySelectorAll('.slide');
    const dots = document.querySelectorAll('.slide-dot');
    
    if (slides.length === 0) return;
    
    slides.forEach(s => s.classList.remove('active'));
    dots.forEach(d => d.classList.remove('active'));
    
    if (index >= slides.length) index = 0;
    if (index < 0) index = slides.length - 1;
    
    slides[index].classList.add('active');
    dots[index].classList.add('active');
    currentSlide = index;
}

function changeSlide(direction) {
    const slides = document.querySelectorAll('.slide');
    if (slides.length === 0) return;
    const newIndex = currentSlide + direction;
    goToSlide(newIndex);
    resetTimer();
}

function nextSlide() {
    const slides = document.querySelectorAll('.slide');
    if (slides.length === 0) return;
    const newIndex = (currentSlide + 1) % slides.length;
    goToSlide(newIndex);
}

function resetTimer() {
    clearInterval(slideInterval);
    slideInterval = setInterval(nextSlide, slideDuration);
}

// Initialize slideshow
document.addEventListener('DOMContentLoaded', function() {
    initSlideshow();
    
    if (slidesData.length > 1) {
        slideInterval = setInterval(nextSlide, slideDuration);
    }
    
    // Pause on hover
    const container = document.getElementById('slideshow');
    container.addEventListener('mouseenter', function() {
        clearInterval(slideInterval);
    });
    container.addEventListener('mouseleave', function() {
        if (slidesData.length > 1) {
            slideInterval = setInterval(nextSlide, slideDuration);
        }
    });
    
    // Touch support
    let touchStartX = 0;
    let touchEndX = 0;
    container.addEventListener('touchstart', function(e) {
        touchStartX = e.changedTouches[0].screenX;
    });
    container.addEventListener('touchend', function(e) {
        touchEndX = e.changedTouches[0].screenX;
        handleSwipe();
    });
    function handleSwipe() {
        const swipeThreshold = 50;
        const diff = touchStartX - touchEndX;
        if (Math.abs(diff) > swipeThreshold) {
            if (diff > 0) {
                changeSlide(1);
            } else {
                changeSlide(-1);
            }
        }
    }
});

// Make functions globally accessible
function changeSlide(direction) {
    const slides = document.querySelectorAll('.slide');
    if (slides.length === 0) return;
    const newIndex = currentSlide + direction;
    goToSlide(newIndex);
    resetTimer();
}

function goToSlide(index) {
    const slides = document.querySelectorAll('.slide');
    const dots = document.querySelectorAll('.slide-dot');
    
    if (slides.length === 0) return;
    
    slides.forEach(s => s.classList.remove('active'));
    dots.forEach(d => d.classList.remove('active'));
    
    if (index >= slides.length) index = 0;
    if (index < 0) index = slides.length - 1;
    
    slides[index].classList.add('active');
    dots[index].classList.add('active');
    currentSlide = index;
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>