<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "newreq";
$recaptcha_secret = '6LcQq-8pAAAAAKMSWGDhSgMnH84WbN6eWbpkijE2'; // Replace with your actual secret key

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function verify_recaptcha($response, $secret)
{
    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $data = array(
        'secret' => $secret,
        'response' => $response
    );

    $options = array(
        'http' => array(
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        )
    );

    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    return json_decode($result);
}

function validate_password($password)
{
    $pattern = '/^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/';
    return preg_match($pattern, $password);
}

if (isset($_POST['register'])) {
    // Registration
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    if (!validate_password($password)) {
        echo "Password must be at least 8 characters long and include at least one uppercase letter, one number, and one special character.";
    } else {
        $password_hashed = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $email, $password_hashed);

        if ($stmt->execute()) {
            echo "New record created successfully";
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    }
} elseif (isset($_POST['login'])) {
    // Verify reCAPTCHA
    $recaptcha_response = $_POST['g-recaptcha-response'];
    $recaptcha = verify_recaptcha($recaptcha_response, $recaptcha_secret);

    if ($recaptcha->success) {
        // Login
        $email = $_POST['email'];
        $password = $_POST['password'];
        $remember = isset($_POST['remember']);

        // Check if user is locked out
        if (isset($_SESSION['login_attempts']) && $_SESSION['login_attempts'] >= 3) {
            $last_attempt_time = $_SESSION['last_attempt_time'];
            if (time() - $last_attempt_time < 30) {
                echo "You have been locked out. Please try again in " . (30 - (time() - $last_attempt_time)) . " seconds.";
                exit;
            } else {
                // Reset attempts after 30 seconds
                $_SESSION['login_attempts'] = 0;
            }
        }

        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if (password_verify($password, $row['password'])) {
                $_SESSION['user'] = $row; // Store user details in session
                $_SESSION['login_attempts'] = 0; // Reset login attempts

                if ($remember) {
                    setcookie("email", $email, time() + (86400 * 30), "/"); // 30 days expiration
                    setcookie("token", bin2hex(random_bytes(16)), time() + (86400 * 30), "/");
                }

                header("Location: profile.php");
                exit; // Make sure to call exit after header redirection
            } else {
                // Increment login attempts
                if (!isset($_SESSION['login_attempts'])) {
                    $_SESSION['login_attempts'] = 0;
                }
                $_SESSION['login_attempts'] += 1;
                $_SESSION['last_attempt_time'] = time();

                echo "Invalid password";
            }
        } else {
            echo "No user found with this email";
        }
        $stmt->close();
    } else {
        echo "reCAPTCHA verification failed. Please try again.";
    }
}

$conn->close();
?>