<?php
session_start();
require 'db.php'; // Include database connection

// Check if user is Applicant
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Applicant') {
    header('Location: login.php');
    exit;
}

// Handle Apply for Job
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'apply') {
    $job_id = $_POST['job_id'];
    $cover_letter = $_POST['cover_letter'];

    // Handle file upload (resume)
    if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "uploads/";
        $target_file = $target_dir . basename($_FILES["resume"]["name"]);
        move_uploaded_file($_FILES["resume"]["tmp_name"], $target_file);
        
        // Insert application into the database
        $sql = "INSERT INTO applications (job_id, applicant_id, cover_letter, resume_path) 
                VALUES (?, (SELECT id FROM users WHERE username = ?), ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('isss', $job_id, $_SESSION['username'], $cover_letter, $target_file);
        $stmt->execute();
        
        echo "Application submitted successfully!";
    }
}

// Fetch job posts for applicants to apply
$job_posts_sql = "SELECT * FROM job_posts";
$job_posts_stmt = $conn->prepare($job_posts_sql);
$job_posts_stmt->execute();
$job_posts_result = $job_posts_stmt->get_result();

// Handle Message Submission (for follow-up)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_message') {
    $message = $_POST['message'];
    $recipient_id = $_POST['recipient_id'];

    $sql = "INSERT INTO messages (sender_id, recipient_id, message) 
            VALUES ((SELECT id FROM users WHERE username = ?), ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sis', $_SESSION['username'], $recipient_id, $message);
    $stmt->execute();
    echo "Message sent!";
}

// Fetch HR users to send messages to
$hr_sql = "SELECT id, username FROM users WHERE role = 'HR'";
$hr_stmt = $conn->prepare($hr_sql);
$hr_stmt->execute();
$hr_result = $hr_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FindHire - Applicant Dashboard</title>
</head>
<body>
    <h1>Welcome, <?php echo $_SESSION['username']; ?>!</h1>
    
    <!-- Job Application Form -->
    <h2>Apply for a Job</h2>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="apply">
        
        <label for="job_id">Select Job:</label>
        <select name="job_id" required>
            <?php while ($job = $job_posts_result->fetch_assoc()): ?>
                <option value="<?php echo $job['id']; ?>"><?php echo $job['title']; ?></option>
            <?php endwhile; ?>
        </select><br><br>

        <label for="cover_letter">Cover Letter:</label><br>
        <textarea name="cover_letter" rows="5" required></textarea><br><br>

        <label for="resume">Upload Resume (PDF):</label>
        <input type="file" name="resume" accept=".pdf" required><br><br>

        <button type="submit">Submit Application</button>
    </form>

    <hr>

    <!-- Message Follow-Up Form -->
    <h2>Send Follow-Up Message to HR</h2>
    <form method="POST">
        <input type="hidden" name="action" value="send_message">
        
        <label for="recipient_id">Select HR Representative:</label>
        <select name="recipient_id" required>
            <?php while ($hr = $hr_result->fetch_assoc()): ?>
                <option value="<?php echo $hr['id']; ?>"><?php echo $hr['username']; ?></option>
            <?php endwhile; ?>
        </select><br><br>

        <label for="message">Message:</label><br>
        <textarea name="message" rows="5" required></textarea><br><br>

        <button type="submit">Send Message</button>
    </form>

    <hr>

    <!-- Display Previous Messages -->
    <h2>Previous Messages</h2>
    <?php
    $messages_sql = "SELECT * FROM messages WHERE sender_id = (SELECT id FROM users WHERE username = ?) OR recipient_id = (SELECT id FROM users WHERE username = ?)";
    $messages_stmt = $conn->prepare($messages_sql);
    $messages_stmt->bind_param('ss', $_SESSION['username'], $_SESSION['username']);
    $messages_stmt->execute();
    $messages_result = $messages_stmt->get_result();

    while ($message = $messages_result->fetch_assoc()) {
        $sender_sql = "SELECT username FROM users WHERE id = ?";
        $sender_stmt = $conn->prepare($sender_sql);
        $sender_stmt->bind_param('i', $message['sender_id']);
        $sender_stmt->execute();
        $sender_result = $sender_stmt->get_result();
        $sender = $sender_result->fetch_assoc();

        echo "<p><strong>From: {$sender['username']}</strong><br>";
        echo "<strong>Message:</strong> {$message['message']}<br>";
        echo "<strong>Sent at:</strong> {$message['sent_at']}</p>";
    }
    ?>

    <hr>
    <footer>
        <p><a href="login.php">Logout</a></p>
    </footer>
</body>
</html>
