<?php
require 'config.php';
require 'auth.php';

$pageTitle = 'About';
require 'partials/header.php';
?>
<div class="page-header">
<h1>About This Platform</h1>
<p>What On-Campus Vendor Services is, and how it works.</p>
</div>

<section>
<h2>Our Mission</h2>
<p>On-Campus Vendor Services gives students one place to book a time slot with campus
vendors — printing, laundry, tailoring, tech repair and more — instead of queuing on the
off chance a vendor is free.</p>
</section>

<section>
<h2>How It Works</h2>
<div class="card-grid">
<div class="card">
<div class="card-icon">&#127978;</div>
<h3>1. Browse Vendors</h3>
<p>See every vendor on campus, grouped by category and location.</p>
</div>
<div class="card">
<div class="card-icon">&#128197;</div>
<h3>2. Book a Slot</h3>
<p>Pick a date and time slot that fits your schedule.</p>
</div>
<div class="card">
<div class="card-icon">&#9989;</div>
<h3>3. Manage Bookings</h3>
<p>Edit or cancel your booking anytime from your homepage.</p>
</div>
</div>
</section>

<section>
<h2>Who Runs This</h2>
<p>This platform is a sample project built for the AMIT3253 Cloud Computing for Business
capstone assignment, demonstrating a simple booking/ticketing system deployed on AWS.</p>
</section>
<?php require 'partials/footer.php'; ?>
