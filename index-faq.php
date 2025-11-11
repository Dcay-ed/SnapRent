<?php
require __DIR__ . '/database/db.php';
$title = 'SnapRent - FAQ';

session_start();
$isLoggedIn = isset($_SESSION['uid']); 

if ($isLoggedIn) {
    require __DIR__ . '/partials/header.php'; 
} else {
    require __DIR__ . '/partials/home-header.php'; 
}
?>
<body>

<section class="hero-wrap">

  <div class="hero-background" style="background-image: url('auth/images/BGCamera.jpg');"></div>
  
  <div class="container">
    <div class="hero-foreground" style="background-image: url('auth/images/BGCamera.jpg');">
      <div class="hero-content">
        <h1>SnapRent</h1>
        <div class="kicker">Rent Your Perfect Camera</div>
        <p class="lead">Affordable, flexible, and ready when you are</p>
        <a class="btn-primary rent-btn" href="<?php echo isset($_SESSION['uid']) ? 'Customer/index.php' : 'auth/login.php'; ?>">
          <span class="btn-text">Rent now</span>
          <span class="btn-icon">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
              <path d="M5 12h14m-7-7l7 7-7 7"/>
            </svg>
          </span>
        </a>
      </div>

      <div class="thumb-strip">
        <div class="thumb-item">
          <img src="auth/images/BGCamera.jpg" alt="Camera 1">
        </div>
        <div class="thumb-item">
          <img src="auth/images/BGCamera.jpg" alt="Camera 2">
        </div>
        <div class="thumb-item">
          <img src="auth/images/BGCamera.jpg" alt="Camera 3">
        </div>
        <div class="thumb-item">
          <img src="auth/images/BGCamera.jpg" alt="Camera 4">
        </div>
      </div>
    </div>
  </div>
</section>

    <!-- Camera Categories -->
    <section class="categories">
        <div class="container">
            <div class="categories-container">
                <button class="category-btn active">DSLR</button>
                <button class="category-btn">Mirrorless</button>
                <button class="category-btn">Digicam</button>
                <button class="category-btn">Analog</button>
                <button class="category-btn">Lenses</button>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="faq">
        <div class="container">
            <h2 class="section-title">Frequently Asked Questions</h2>
            <div class="faq-container">
                <div class="faq-item active">
                    <div class="faq-question">
                        How do I place an order?
                        <span class="faq-toggle">▼</span>
                    </div>
                    <div class="faq-answer">
                        <p>Placing an order with SnapRent is simple! Just browse our camera collection, select the camera you want to rent, choose your rental dates, and proceed to checkout. You'll need to create an account, provide some basic information, and complete the payment process. Once your order is confirmed, you'll receive a confirmation email with all the details.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">
                        What is the rental period?
                        <span class="faq-toggle">▼</span>
                    </div>
                    <div class="faq-answer">
                        <p>Our standard rental period is a minimum of 3 days. You can choose your preferred rental dates during the booking process. We offer flexible options for both short-term and long-term rentals. If you need to extend your rental period, please contact our customer service at least 24 hours before your scheduled return date.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">
                        How does delivery work?
                        <span class="faq-toggle">▼</span>
                    </div>
                    <div class="faq-answer">
                        <p>We offer free delivery within the city limits for rentals of 5 days or more. For shorter rentals or locations outside our standard delivery area, a delivery fee may apply. The camera will be delivered to your specified address on the start date of your rental period. You'll need to be present to receive the equipment and sign for it.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">
                        What if the camera gets damaged during my rental?
                        <span class="faq-toggle">▼</span>
                    </div>
                    <div class="faq-answer">
                        <p>All our cameras come with basic protection against accidental damage. However, we strongly recommend purchasing our optional damage waiver for complete peace of mind. In case of any damage, please contact us immediately. The renter is responsible for any loss or theft of equipment during the rental period.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">
                        Can I cancel or modify my reservation?
                        <span class="faq-toggle">▼</span>
                    </div>
                    <div class="faq-answer">
                        <p>Yes, you can cancel or modify your reservation up to 48 hours before your rental start date without any penalty. Cancellations made within 48 hours of the rental start date may be subject to a cancellation fee. To modify or cancel your reservation, log into your account or contact our customer service team.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">
                        What payment methods do you accept?
                        <span class="faq-toggle">▼</span>
                    </div>
                    <div class="faq-answer">
                        <p>We accept all major credit cards (Visa, MasterCard, American Express), debit cards, and digital payments through various platforms. Payment is required in full at the time of booking. For corporate accounts, we also offer invoice-based billing with prior approval.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">
                        Do you provide accessories with the cameras?
                        <span class="faq-toggle">▼</span>
                    </div>
                    <div class="faq-answer">
                        <p>Yes, each camera rental comes with essential accessories including a battery, charger, and memory card (if applicable). Additional accessories like lenses, tripods, lighting equipment, and camera bags are available for rent separately. You can add these to your order during the booking process.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">
                        What is your late return policy?
                        <span class="faq-toggle">▼</span>
                    </div>
                    <div class="faq-answer">
                        <p>We understand that sometimes plans change. If you need to extend your rental, please contact us as soon as possible. Late returns without prior approval will incur additional charges at 1.5x the daily rate. Repeated late returns may affect your ability to rent from us in the future.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php require __DIR__ . '/partials/footer.php'; ?>

    <script>
        // FAQ Toggle Functionality
        document.addEventListener('DOMContentLoaded', function() {
            const faqItems = document.querySelectorAll('.faq-item');
            
            faqItems.forEach(item => {
                const question = item.querySelector('.faq-question');
                
                question.addEventListener('click', () => {
                    // Close all other items
                    faqItems.forEach(otherItem => {
                        if (otherItem !== item) {
                            otherItem.classList.remove('active');
                        }
                    });
                    
                    // Toggle current item
                    item.classList.toggle('active');
                });
            });
            
            // Category buttons functionality
            const categoryBtns = document.querySelectorAll('.category-btn');
            
            categoryBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    categoryBtns.forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                });
            });
            
            // Navigation active link
            const navLinks = document.querySelectorAll('.nav-links a');
            
            navLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    if (this.getAttribute('href') === '#') {
                        e.preventDefault();
                    }
                    
                    navLinks.forEach(l => l.classList.remove('active'));
                    this.classList.add('active');
                });
            });
        });
    </script>
</body>
</html>