<?php
require 'config.php';
require 'auth.php';

$pageTitle = 'About';
require 'partials/header.php';
?>
<div class="page-header">
<h1>About This Platform</h1>
<p>What Academic &amp; Library Resource Scheduling is, and how it works.</p>
</div>

<section>
<h2>Our Mission</h2>
<p>Academic &amp; Library Resource Scheduling lets students reserve a discussion room or
silent study pod, and loan academic equipment like laptops and projectors, ahead of time
instead of wandering the library floors hoping something is free during exam week.</p>
</section>

<section>
<h2>How It Works</h2>
<div class="card-grid">
<div class="card">
<div class="card-icon">&#128220;</div>
<h3>1. Browse Resources</h3>
<p>See every discussion room, study pod, and equipment item, with its details and availability.</p>
</div>
<div class="card">
<div class="card-icon">&#128197;</div>
<h3>2. Pick a Slot</h3>
<p>Choose a date and time slot that works for your group.</p>
</div>
<div class="card">
<div class="card-icon">&#9989;</div>
<h3>3. Manage Bookings &amp; Loans</h3>
<p>Edit or cancel your room booking or equipment loan anytime from your homepage.</p>
</div>
</div>
</section>

<section>
<h2>Who Runs This</h2>
<p>This platform is a sample project built for the AMIT3253 Cloud Computing for Business
capstone assignment, demonstrating a simple booking/ticketing system deployed on AWS.</p>
</section>
<?php require 'partials/footer.php'; ?>
