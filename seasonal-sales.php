<?php
require_once __DIR__ . '/includes/init.php';
includeHeader('Seasonal Sales and Events');
?>
<div class="container">
    <div class="page-header"><h1>Seasonal Sales & Events</h1><p>Don't miss our biggest deals of the year</p></div>
    <div class="content-wrapper">
        <section><h2>Year-Round Savings</h2><p>FezaMarket hosts exciting sales events throughout the year, offering incredible deals on millions of products. Mark your calendar for these major shopping events!</p></section>
        <section><h2>Major Annual Events</h2>
            <div class="events-grid">
                <div class="event-card"><h3>🎉 New Year Sale</h3><p>Start the year with up to 70% off</p><p class="date">January 1-7</p></div>
                <div class="event-card"><h3>💝 Valentine's Day</h3><p>Gifts for loved ones at special prices</p><p class="date">February 10-14</p></div>
                <div class="event-card"><h3>🌸 Spring Sale</h3><p>Fresh deals for a fresh season</p><p class="date">March 20-31</p></div>
                <div class="event-card"><h3>👨 Father's Day</h3><p>Perfect gifts for dad</p><p class="date">June 15-19</p></div>
                <div class="event-card"><h3>🏫 Back to School</h3><p>Everything for students and teachers</p><p class="date">August 1-31</p></div>
                <div class="event-card"><h3>🛍️ Black Friday</h3><p>Our biggest sale of the year</p><p class="date">November 24-27</p></div>
                <div class="event-card"><h3>💻 Cyber Monday</h3><p>Massive tech and electronics deals</p><p class="date">November 27</p></div>
                <div class="event-card"><h3>🎄 Holiday Sale</h3><p>Give the gift of savings</p><p class="date">December 1-24</p></div>
            </div>
        </section>
        <section><h2>Flash Sales & Daily Deals</h2><p>Beyond major events, we offer:</p>
            <ul><li><strong>Lightning Deals:</strong> Limited quantity offers that sell out fast</li><li><strong>Daily Deals:</strong> New discounts every day across all categories</li><li><strong>Weekend Specials:</strong> Exclusive offers every Friday-Sunday</li><li><strong>Category Sales:</strong> Rotating deep discounts on specific categories</li></ul>
        </section>
        <section><h2>How to Stay Updated</h2>
            <div class="updates-grid">
                <div class="update-card"><h3>📧 Email Alerts</h3><p>Get notified before sales start</p></div>
                <div class="update-card"><h3>📱 Mobile App</h3><p>Push notifications for flash deals</p></div>
                <div class="update-card"><h3>🔔 Follow Us</h3><p>Social media updates and exclusive codes</p></div>
            </div>
        </section>
    </div>
</div>
<style>
.page-header{text-align:center;padding:50px 20px;background:linear-gradient(135deg,#f59e0b 0%,#d97706 100%);color:white;margin-bottom:50px}
.page-header h1{margin:0 0 10px 0;font-size:42px}
.content-wrapper{max-width:1200px;margin:0 auto;padding:0 20px 60px}
section{margin-bottom:50px}section h2{font-size:32px;margin-bottom:20px;color:#1f2937;border-bottom:2px solid #f59e0b;padding-bottom:10px}
.events-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:20px}
.event-card{background:white;padding:25px;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.1);text-align:center}
.event-card h3{margin:0 0 10px 0;font-size:20px}
.event-card .date{color:#f59e0b;font-weight:bold;margin-top:10px}
.updates-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:25px}
.update-card{background:#f9fafb;padding:30px;border-radius:12px;text-align:center}
.update-card h3{margin:0 0 15px 0}
@media (max-width:768px){.events-grid{grid-template-columns:1fr}}
</style>
<?php includeFooter(); ?>