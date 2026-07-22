<?php
// Use legacy-style error reporting: mysqli functions return false on
// failure (e.g. a duplicate booking) instead of throwing an exception,
// so ordinary "if (!$stmt->execute())" checks work as expected below.
mysqli_report(MYSQLI_REPORT_OFF);

// TAR UMT is in Malaysia (UTC+8), but this server's OS clock defaults to UTC
// (true both locally in Docker and on a stock EC2 instance) - without this,
// every date()/time() call here (booking date validation, "today" defaults,
// past-slot checks) runs 8 hours behind real local time.
date_default_timezone_set('Asia/Kuala_Lumpur');

// ============================================================================
// Database connection
// ============================================================================
// LOCAL / DOCKER (current default below): connects to a MySQL server on this
// same machine using the credentials below.
//
// AWS RDS (Phase 3 of the assignment): once you provision an Amazon RDS
// MySQL instance, point this app at it. Recommended: set these as
// environment variables on your EC2 instance / Apache vhost so this file
// never needs to change between local, EC2, and RDS:
//
//   DB_HOST = your-db-identifier.xxxxxxxxxxxx.us-east-1.rds.amazonaws.com
//   DB_USER = admin              (the master username you set when creating the RDS instance)
//   DB_PASS = ********           (the master password you set when creating the RDS instance)
//   DB_NAME = sports_booking_db
//
// Or, to hardcode it instead of using environment variables, replace the
// fallback values below directly, e.g.:
//   $host = 'your-db-identifier.xxxxxxxxxxxx.us-east-1.rds.amazonaws.com';
//   $user = 'admin';
//   $pass = 'your-rds-master-password';
// ============================================================================
$host   = getenv('DB_HOST') ?: 'localhost';
$user   = getenv('DB_USER') ?: 'root';
$pass   = getenv('DB_PASS') ?: '';
$dbname = getenv('DB_NAME') ?: 'sports_booking_db';

// @-suppressed: even with MYSQLI_REPORT_OFF (no exception), a failed
// connection still emits a PHP-level warning straight into the response
// body. With display_errors on, that warning is output before the
// connect_error check below runs, which flushes headers with the default
// 200 status - making the http_response_code(500) call too late to matter.
// Suppressing it here keeps the failure check as the single source of truth
// for what gets reported.
$conn = @new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    // Signal unhealthy to an ALB health check (or anything else probing this
    // page) instead of silently returning 200 OK with an error message body -
    // otherwise a target group would keep routing real traffic to an
    // instance that can't reach its database.
    http_response_code(500);
    die('Database connection failed: ' . $conn->connect_error);
}

// Keep MySQL's NOW()/CURRENT_TIMESTAMP in step with the PHP timezone above -
// otherwise created_at/returned_at etc. would still be recorded 8 hours off.
$conn->query("SET time_zone = '+08:00'");

// ============================================================================
// Photo storage (S3) - optional
// ============================================================================
// LOCAL / DOCKER (current default below, both blank): uploaded photos are
// saved to this app's local uploads/ folder, exactly as before. Nothing to
// configure for local development.
//
// Once there's more than one EC2 instance behind an ALB/ASG, local disk
// uploads only exist on whichever instance handled that request - the next
// instance (or a newly launched ASG instance) won't have the file, so the
// image breaks. Uploading to S3 instead fixes this, since every instance
// reads/writes the same bucket. This is already wired up (see helpers.php)
// - to turn it on:
//   1. Create an S3 bucket and a bucket policy allowing public s3:GetObject
//      on it (or put CloudFront in front of it instead).
//   2. Attach an IAM role to your EC2 instance/launch template with an
//      s3:PutObject + s3:DeleteObject permission scoped to that bucket -
//      credentials are then fetched automatically from the instance's own
//      metadata service (IMDSv2), so nothing is hardcoded here.
//   3. Set these two values (env vars, or hardcode them like DB_HOST above):
//      AWS_S3_BUCKET = your-bucket-name
//      AWS_S3_REGION = us-east-1
// ============================================================================
define('AWS_S3_BUCKET', getenv('AWS_S3_BUCKET') ?: '');
define('AWS_S3_REGION', getenv('AWS_S3_REGION') ?: 'us-east-1');
