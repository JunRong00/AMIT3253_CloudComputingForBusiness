<?php
require 'config.php';
require 'auth.php';

$pageTitle = 'About';
require 'partials/header.php';
?>
<div class="page-header">
<h1>About This Platform</h1>
<p>What Campus Shuttle Bus Ticketing is, and how it works.</p>
</div>

<section>
<h2>Our Mission</h2>
<p>Campus Shuttle Bus Ticketing lets students and staff reserve a seat on a campus shuttle
route ahead of time, instead of queuing at the bus stop and hoping a seat is free.</p>
</section>

<section>
<h2>How It Works</h2>
<div class="card-grid">
<div class="card">
<div class="card-icon">&#128652;</div>
<h3>1. Browse Routes</h3>
<p>See every shuttle route with its departure time, price and seat capacity.</p>
</div>
<div class="card">
<div class="card-icon">&#127903;</div>
<h3>2. Book a Ticket</h3>
<p>Choose your travel date and number of seats.</p>
</div>
<div class="card">
<div class="card-icon">&#9989;</div>
<h3>3. Manage Tickets</h3>
<p>Change or cancel your ticket anytime from your homepage.</p>
</div>
</div>
</section>

<section>
<h2>Who Runs This</h2>
<p>This platform is a sample project built for the AMIT3253 Cloud Computing for Business
capstone assignment, demonstrating a simple booking/ticketing system deployed on AWS.</p>
</section>
<?php require 'partials/footer.php'; ?>
