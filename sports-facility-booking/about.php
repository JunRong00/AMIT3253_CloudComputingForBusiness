<?php
require 'config.php';
require 'auth.php';

$bookingCount = $conn->query('SELECT COUNT(*) AS c FROM bookings')->fetch_assoc()['c'];
$memberCount  = $conn->query('SELECT COUNT(*) AS c FROM users WHERE is_admin = 0')->fetch_assoc()['c'];

$pageTitle = 'About Us';
require 'partials/header.php';
?>
<div class="about-hero">
<div class="about-hero-icons">&#127992; &#9917; &#127934; &#127947;</div>
<h1>Campus Sports Facility Booking</h1>
<p>Serving the TAR UMT community since 2010</p>
</div>

<section>
<h2>Our Story</h2>
<p>Founded in 2010, the TAR UMT Sports Complex has grown from a single badminton hall
into a full multi-sport facility serving thousands of students and staff every year.
What started as a paper sign-up sheet at the front counter is now this online booking
platform — built to replace manual queues with a simple, transparent system so anyone
on campus can check what's free and reserve a slot in under a minute.</p>
</section>

<section>
<h2>How It Works</h2>
<div class="timeline">
<div class="timeline-step timeline-step-left">
<div class="timeline-marker"></div>
<div class="timeline-content">
<h3>1. Browse Facilities</h3>
<p>Explore available facilities and check real-time schedules before you head over.</p>
</div>
</div>
<div class="timeline-step timeline-step-right">
<div class="timeline-marker"></div>
<div class="timeline-content">
<h3>2. Check Availability</h3>
<p>View each time slot's status on the Schedule page — Available, Booked or Closed.</p>
</div>
</div>
<div class="timeline-step timeline-step-left">
<div class="timeline-marker"></div>
<div class="timeline-content">
<h3>3. Make a Booking</h3>
<p>Pick a date and time slot that works for you — it's reserved the moment you confirm.</p>
</div>
</div>
<div class="timeline-step timeline-step-right">
<div class="timeline-marker"></div>
<div class="timeline-content">
<h3>4. Get Confirmation</h3>
<p>Receive an instant on-screen confirmation with a reference number for your records.</p>
</div>
</div>
<div class="timeline-step timeline-step-left">
<div class="timeline-marker"></div>
<div class="timeline-content">
<h3>5. Enjoy Your Game</h3>
<p>Show up at your slot. No queueing, no double-booking, no fuss.</p>
</div>
</div>
</div>
</section>

<section>
<h2>By the Numbers</h2>
<div class="card-grid">
<div class="stat-tile"><div class="stat-value"><?= (int)$bookingCount ?></div><div class="stat-label">Bookings made</div></div>
<div class="stat-tile"><div class="stat-value"><?= (int)$memberCount ?></div><div class="stat-label">Members registered</div></div>
</div>
</section>
<div class="card-actions">
<a class="btn btn-secondary btn-small" href="facilities.php">See what's available &rarr;</a>
<a class="btn btn-secondary btn-small" href="contact.php">Contact us &rarr;</a>
</div>
<?php require 'partials/footer.php'; ?>
