<?php
// Load PHPMailer classes
require_once("php-mailer/PHPMailer.php");
require_once("php-mailer/SMTP.php");
require_once("php-mailer/Exception.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Database config
$host = 'localhost';
$db   = 'testdb';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

// DSN and options for PDO
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

// SMTP config - replace with your SMTP details
$smtpHost = 'smtp.gmail.com';
$smtpUsername = 'smtpemail';
$smtpPassword = 'smtppassword';
$smtpPort = 587; // or 465 for SSL
$smtpSecure = 'ssl'; // or 'ssl'
$adminEmail = 'adminemail';

session_start();

$alert = null;
$name = $email = $message = '';
$subjects = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize inputs
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subjects = $_POST['subject'] ?? [];
    $message = trim($_POST['message'] ?? '');

    // Basic validation
    if (!$name || !$email || empty($subjects) || !$message) {
        $_SESSION['alert'] = [
            'icon' => 'error',
            'title' => 'Validation error',
            'text' => 'Please fill all fields and select at least one subject.'
        ];
        $_SESSION['form_data'] = compact('name', 'email', 'subjects', 'message');
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['alert'] = [
            'icon' => 'error',
            'title' => 'Invalid email',
            'text' => 'Please enter a valid email address.'
        ];
        $_SESSION['form_data'] = compact('name', 'email', 'subjects', 'message');
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } else {
        try {
            $pdo = new PDO($dsn, $user, $pass, $options);

            // Insert into DB
            $stmt = $pdo->prepare("INSERT INTO contacts (name, email, subject, message) VALUES (:name, :email, :subject, :message)");
            $subjectStr = implode(',', $subjects);
            $stmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':subject' => $subjectStr,
                ':message' => $message,
            ]);

            // Send Email with PHPMailer
            $mail = new PHPMailer(true);
            try {
                //Server settings
                $mail->isSMTP();
                $mail->Host       = $smtpHost;
                $mail->SMTPAuth   = true;
                $mail->Username   = $smtpUsername;
                $mail->Password   = $smtpPassword;
                $mail->SMTPSecure = $smtpSecure;
                $mail->Port       = $smtpPort;

                //Recipients
                $mail->setFrom($smtpUsername, 'Contact Form');
                $mail->addAddress($adminEmail); // Admin
                $mail->addCC('extraEmail'); // CC

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'New Contact Form Submission';
                $mail->Body    = "<h3>New contact message</h3>
                                  <p><strong>Name:</strong> ".htmlspecialchars($name)."</p>
                                  <p><strong>Email:</strong> ".htmlspecialchars($email)."</p>
                                  <p><strong>Subject(s):</strong> ".htmlspecialchars($subjectStr)."</p>
                                  <p><strong>Message:</strong><br>".nl2br(htmlspecialchars($message))."</p>";

                $mail->send();

                $_SESSION['alert'] = [
                    'icon' => 'success',
                    'title' => 'Success!',
                    'text' => 'Your message has been sent and saved.'
                ];

                // Redirect after success to prevent resubmission
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;

            } catch (Exception $e) {
                $_SESSION['alert'] = [
                    'icon' => 'error',
                    'title' => 'Mailer Error',
                    'text' => 'Message could not be sent. Mailer Error: ' . $mail->ErrorInfo
                ];
                $_SESSION['form_data'] = compact('name', 'email', 'subjects', 'message');
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }

        } catch (PDOException $e) {
            $_SESSION['alert'] = [
                'icon' => 'error',
                'title' => 'Database error',
                'text' => 'Could not save your message. Please try again later.'
            ];
            $_SESSION['form_data'] = compact('name', 'email', 'subjects', 'message');
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
    }
}

// Retrieve alert and form data from session if available
if (isset($_SESSION['alert'])) {
    $alert = $_SESSION['alert'];
    unset($_SESSION['alert']);
}

if (isset($_SESSION['form_data'])) {
    $form_data = $_SESSION['form_data'];
    $name = $form_data['name'] ?? '';
    $email = $form_data['email'] ?? '';
    $subjects = $form_data['subjects'] ?? [];
    $message = $form_data['message'] ?? '';
    unset($_SESSION['form_data']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Contact Form with PHPMailer, Select2 & SweetAlert2</title>

  <!-- Select2 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

  <!-- SweetAlert2 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet" />

  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f9fafb;
      display: flex;
      justify-content: center;
      align-items: flex-start;
      min-height: 100vh;
      padding: 40px 20px;
    }
    form {
      background: white;
      padding: 30px 40px;
      border-radius: 12px;
      box-shadow: 0 8px 24px rgba(0,0,0,0.1);
      width: 100%;
      max-width: 480px;
    }
    h2 {
      margin-bottom: 24px;
      color: #ec4899;
      text-align: center;
    }
    label {
      display: block;
      margin-bottom: 6px;
      font-weight: 600;
      color: #374151;
    }
    input[type="text"],
    input[type="email"],
    textarea,
    select {
      width: 100%;
      padding: 10px 14px;
      margin-bottom: 18px;
      border: 1.5px solid #d1d5db;
      border-radius: 8px;
      font-size: 1rem;
      transition: border-color 0.3s ease;
    }
    input[type="text"]:focus,
    input[type="email"]:focus,
    textarea:focus,
    select:focus {
      border-color: #ec4899;
      outline: none;
      box-shadow: 0 0 5px #f472b6;
    }
    textarea {
      resize: vertical;
      min-height: 100px;
    }
    button {
      background-color: #ec4899;
      color: white;
      border: none;
      border-radius: 10px;
      padding: 12px 0;
      font-size: 1.1rem;
      font-weight: 700;
      cursor: pointer;
      width: 100%;
      transition: background-color 0.3s ease;
    }
    button:hover {
      background-color: #be185d;
    }
  </style>
</head>
<body>

  <form method="post" id="contactForm" novalidate>
    <h2>Contact Us</h2>

    <label for="name">Name</label>
    <input type="text" id="name" name="name" value="<?= htmlspecialchars($name) ?>" required />

    <label for="email">Email</label>
    <input type="email" id="email" name="email" value="<?= htmlspecialchars($email) ?>" required />

    <label for="subject">Subject(s)</label>
    <select id="subject" name="subject[]" multiple="multiple" required style="width: 100%;">
      <?php
      $options = ['General Inquiry', 'Support', 'Sales', 'Feedback', 'Other'];
      foreach ($options as $opt) {
          $selected = (in_array($opt, $subjects)) ? 'selected' : '';
          echo "<option value=\"$opt\" $selected>$opt</option>";
      }
      ?>
    </select>

    <label for="message">Message</label>
    <textarea id="message" name="message" required><?= htmlspecialchars($message) ?></textarea>

    <button type="submit">Send Message</button>
  </form>

  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

  <!-- Select2 JS -->
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

  <!-- SweetAlert2 JS -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <script>
    $(document).ready(function() {
      $('#subject').select2({
        placeholder: "Select subject(s)",
        allowClear: true
      });

      <?php if ($alert): ?>
        Swal.fire({
          icon: <?= json_encode($alert['icon']) ?>,
          title: <?= json_encode($alert['title']) ?>,
          text: <?= json_encode($alert['text']) ?>,
          confirmButtonColor: '#ec4899',
          background: '#fff0f6',
          customClass: { popup: 'swal2-popup-rounded' }
        });
      <?php endif; ?>
    });
  </script>

  <style>
    .swal2-popup-rounded {
      border-radius: 20px !important;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif !important;
    }
  </style>

</body>
</html>