<?php
session_start();
require 'db.php'; // Include database connection

// Check if user is HR
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'HR') {
    header('Location: login.php');
    exit;
}

// Handle Job Post Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'post_job') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $requirements = $_POST['requirements'];
    $created_by = $_SESSION['username'];

    $sql = "INSERT INTO job_posts (title, description, requirements, created_by) 
            VALUES (?, ?, ?, (SELECT id FROM users WHERE username = ?))";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssss', $title, $description, $requirements, $created_by);
    $stmt->execute();
    echo "Job post created successfully!";
}

// Fetch job posts created by HR
$sql = "SELECT * FROM job_posts WHERE created_by = (SELECT id FROM users WHERE username = ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $_SESSION['username']);
$stmt->execute();
$job_posts_result = $stmt->get_result();

// Handle Accept/Reject Application
if (isset($_GET['id']) && isset($_GET['action'])) {
    $application_id = $_GET['id'];
    $action = $_GET['action'];

    if ($action === 'accept') {
        $update_sql = "UPDATE applications SET status = 'Accepted' WHERE id = ?";
    } elseif ($action === 'reject') {
        $update_sql = "UPDATE applications SET status = 'Rejected' WHERE id = ?";
    }

    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param('i', $application_id);
    $update_stmt->execute();
}

// Fetch applications for HR
$applications_sql = "SELECT a.id, j.title, u.username, a.status 
                     FROM applications a 
                     JOIN job_posts j ON a.job_id = j.id
                     JOIN users u ON a.applicant_id = u.id
                     WHERE j.created_by = (SELECT id FROM users WHERE username = ?)";
$applications_stmt = $conn->prepare($applications_sql);
$applications_stmt->bind_param('s', $_SESSION['username']);
$applications_stmt->execute();
$applications_result = $applications_stmt->get_result();

// Handle Message Submission
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

// Fetch messages for HR
$messages_sql = "SELECT m.message, u.username 
                 FROM messages m 
                 JOIN users u ON m.sender_id = u.id
                 WHERE m.recipient_id = (SELECT id FROM users WHERE username = ?)";
$messages_stmt = $conn->prepare($messages_sql);
$messages_stmt->bind_param('s', $_SESSION['username']);
$messages_stmt->execute();
$messages_result = $messages_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Dashboard - FindHire</title>
</head>
<body>
    <h1>Welcome, <?php echo $_SESSION['username']; ?> (HR)</h1>
    <h2>Job Posts</h2>
    
    <!-- Form to Create a Job Post -->
    <form method="POST">
        <input type="hidden" name="action" value="post_job">
        <label for="title">Job Title:</label><br>
        <input type="text" name="title" required><br><br>

        <label for="description">Job Description:</label><br>
        <textarea name="description" rows="5" required></textarea><br><br>

        <label for="requirements">Job Requirements:</label><br>
        <textarea name="requirements" rows="5" required></textarea><br><br>

        <button type="submit">Post Job</button>
    </form>

    <h2>Existing Job Posts</h2>
    <table border="1">
        <tr>
            <th>Job Title</th>
            <th>Description</th>
        </tr>
        <?php while ($row = $job_posts_result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['title']; ?></td>
                <td><?php echo $row['description']; ?></td>
            </tr>
        <?php endwhile; ?>
    </table>

    <h2>Applications</h2>
    <table border="1">
        <tr>
            <th>Job Title</th>
            <th>Applicant</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
        <?php while ($application = $applications_result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $application['title']; ?></td>
                <td><?php echo $application['username']; ?></td>
                <td><?php echo $application['status']; ?></td>
                <td>
                    <a href="hr_dashboard.php?id=<?php echo $application['id']; ?>&action=accept">Accept</a> | 
                    <a href="hr_dashboard.php?id=<?php echo $application['id']; ?>&action=reject">Reject</a>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>

    <h2>Messages from Applicants</h2>
    <table border="1">
        <tr>
            <th>Sender</th>
            <th>Message</th>
        </tr>
        <?php while ($message = $messages_result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $message['username']; ?></td>
                <td><?php echo $message['message']; ?></td>
            </tr>
        <?php endwhile; ?>
    </table>

    <h2>Send a Reply</h2>
    <form method="POST">
        <input type="hidden" name="action" value="send_message">
        <textarea name="message" rows="5" required></textarea><br><br>
        <label for="recipient_id">Select Recipient (Applicant):</label><br>
        <select name="recipient_id" required>
            <!-- Add options here based on HR's contacts or applicants -->
            <option value="1">Applicant 1</option>
            <option value="2">Applicant 2</option>
        </select><br><br>
        <button type="submit">Send Reply</button>
    </form>

</body>
</html>
