<?php
session_start();

// Include database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "newreq";

$conn = mysqli_connect($servername, $username, $password, $dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: index.html");
    exit;
}

$user = $_SESSION['user'];
$message = "";

// Handle profile picture upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["profile-picture"])) {
    $target_dir = "uploads/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $target_file = $target_dir . basename($_FILES["profile-picture"]["name"]);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    $check = getimagesize($_FILES["profile-picture"]["tmp_name"]);
    if ($check !== false) {
        $uploadOk = 1;
    } else {
        $message = "File is not an image.";
        $uploadOk = 0;
    }

    if (file_exists($target_file)) {
        $message = "Sorry, file already exists.";
        $uploadOk = 0;
    }

    if ($_FILES["profile-picture"]["size"] > 500000) {
        $message = "Sorry, your file is too large.";
        $uploadOk = 0;
    }

    if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
        $message = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
        $uploadOk = 0;
    }

    if ($uploadOk == 0) {
        $message = "Sorry, your file was not uploaded. " . $message;
    } else {
        if (move_uploaded_file($_FILES["profile-picture"]["tmp_name"], $target_file)) {
            $message = "The file " . htmlspecialchars(basename($_FILES["profile-picture"]["name"])) . " has been uploaded.";
            $uploaded_profile_picture = $target_file;

            // Save profile picture path to the database
            $sql = "UPDATE users SET profile_picture = '$target_file' WHERE id = " . $_SESSION['user']['id'];
            if (mysqli_query($conn, $sql)) {
                $_SESSION['user']['profile_picture'] = $target_file;
            } else {
                $message = "Error updating record: " . mysqli_error($conn);
            }
        } else {
            $message = "Sorry, there was an error uploading your file.";
        }
    }
}

// Handle profile picture removal
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['remove-picture'])) {
    $profile_picture = $_SESSION['user']['profile_picture'] ?? null;
    if ($profile_picture && file_exists($profile_picture)) {
        unlink($profile_picture);
        $message = "Profile picture removed.";

        // Remove profile picture path from the database
        $sql = "UPDATE users SET profile_picture = NULL WHERE id = " . $_SESSION['user']['id'];
        if (mysqli_query($conn, $sql)) {
            unset($_SESSION['user']['profile_picture']);
        } else {
            $message = "Error updating record: " . mysqli_error($conn);
        }
    } else {
        $message = "No profile picture to remove.";
    }
}

$profile_picture = $_SESSION['user']['profile_picture'] ?? 'https://via.placeholder.com/150';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <style>
        @import url("https://fonts.googleapis.com/css?family=Montserrat:400,400i,700");

        body {
            font-size: 16px;
            color: #404040;
            font-family: Montserrat, sans-serif;
            background-image: linear-gradient(to bottom right, #ff9eaa 0% 65%, #e860ff 95% 100%);
            background-position: center;
            background-attachment: fixed;
            margin: 0;
            padding: 2rem 0;
            display: grid;
            place-items: center;
            box-sizing: border-box;
        }

        .card {
            background-color: white;
            max-width: 480px;
            margin: auto;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            border-radius: 2rem;
            box-shadow: 0px 1rem 1.5rem rgba(0, 0, 0, 0.5);
            padding-bottom: 1rem;
        }

        .banner {
            background-image: url(https://images.unsplash.com/photo-1545703549-7bdb1d01b734?ixlib=rb-1.2.1&q=80&fm=jpg&crop=entropy&cs=tinysrgb&w=400&fit=max&ixid=eyJhcHBfaWQiOjE0NTg5fQ);
            background-position: center;
            background-repeat: no-repeat;
            background-size: cover;
            height: 11rem;
            display: flex;
            align-items: flex-end;
            justify-content: center;
            box-sizing: border-box;
        }

        .banner svg,
        .banner img {
            background-color: white;
            width: 8rem;
            height: 8rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.3);
            border-radius: 50%;
            transform: translateY(50%);
            transition: transform 200ms cubic-bezier(0.18, 0.89, 0.32, 1.28);
        }

        .banner svg:hover,
        .banner img:hover {
            transform: translateY(50%) scale(1.3);
        }

        .menu {
            width: 100%;
            height: 5.5rem;
            padding: 1rem;
            display: flex;
            align-items: flex-start;
            justify-content: flex-end;
            position: relative;
            box-sizing: border-box;
        }

        .menu .opener {
            width: 2.5rem;
            height: 2.5rem;
            position: relative;
            border-radius: 50%;
            transition: background-color 100ms ease-in-out;
        }

        .menu .opener:hover {
            background-color: #f2f2f2;
        }

        .menu .opener span {
            background-color: #404040;
            width: 0.4rem;
            height: 0.4rem;
            position: absolute;
            border-radius: 50%;
        }

        .menu .opener span:nth-child(1) {
            top: 0.45rem;
            left: calc(50% - 0.2rem);
        }

        .menu .opener span:nth-child(2) {
            top: 1.05rem;
            left: calc(50% - 0.2rem);
        }

        .menu .opener span:nth-child(3) {
            top: 1.65rem;
            left: calc(50% - 0.2rem);
        }

        h2.name {
            text-align: center;
            padding: 0 2rem 0.5rem;
            margin: 0;
        }

        .title {
            color: #b0b0b0;
            font-size: 0.85rem;
            text-align: center;
            padding: 0 2rem 1.2rem;
        }

        .actions {
            padding: 0 2rem 1.2rem;
            display: flex;
            flex-direction: column;
        }

        .actions .follow-info {
            padding: 0 0 1rem;
            display: flex;
        }

        .actions .follow-info h2 {
            text-align: center;
            width: 50%;
            margin: 0;
            box-sizing: border-box;
        }

        .actions .follow-info h2 a {
            text-decoration: none;
            padding: 0.8rem;
            display: flex;
            flex-direction: column;
            border-radius: 0.8rem;
            transition: background-color 100ms ease-in-out;
        }

        .actions .follow-info h2 a span {
            color: #1c9eff;
            font-weight: bold;
            transform-origin: bottom;
            transform: scaleY(1.3);
            transition: color 100ms ease-in-out;
        }

        .actions .follow-info h2 a small {
            color: #afafaf;
            font-size: 0.85rem;
            font-weight: normal;
        }

        .actions .follow-info h2 a:hover {
            background-color: #f2f2f2;
        }

        .actions .follow-info h2 a:hover span {
            color: #007ad6;
        }

        .actions .follow-btn button {
            color: inherit;
            font: inherit;
            font-weight: bold;
            background-color: #ffd01a;
            width: 100%;
            border: none;
            padding: 1rem;
            outline: none;
            box-sizing: border-box;
            border-radius: 1.5rem / 50%;
            transition: background-color 100ms ease-in-out, transform 200ms cubic-bezier(0.18, 0.89, 0.32, 1.28);
        }

        .actions .follow-btn button:hover {
            background-color: #efb10a;
            transform: scale(1.1);
        }

        .actions .follow-btn button:active {
            background-color: #e8a200;
            transform: scale(1);
        }

        .desc {
            text-align: justify;
            padding: 0 2rem 2.5rem;
        }

        .container {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .container form {
            width: 100%;
            max-width: 300px;
            margin-bottom: 1rem;
        }

        .container form button {
            width: 100%;
            margin-top: 0.5rem;
        }
    </style>
</head>

<body>
    <div class="card">
        <div class="banner">
            <?php if (isset($_SESSION['user']['profile_picture'])): ?>
                <img src="<?php echo $_SESSION['user']['profile_picture']; ?>" alt="Profile Picture">
            <?php else: ?>
                <svg viewBox="0 0 24 24">
                    <path fill="#404040"
                        d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4m0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" />
                        </svg>
                <?php endif; ?>
        </div>
        <div class="menu">
            <div class="opener">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
        <h2 class="name"><?php echo $user['name']; ?>
                </h2>
                <div class="title">Software Engineer</div>
                <div class="actions">
                    <div class="follow-info">
                        <h2><a href="#"><span>456</span><small>Followers</small></a></h2>
                        <h2><a href="#"><span>128</span><small>Following</small></a></h2>
                    </div>
                    <div class="follow-btn">
                        <button>Follow</button>
                    </div>
                </div>
                <div class="desc">Big Fan</div>
        </div>

        <div class="container">
            <form method="post" enctype="multipart/form-data">
                <input type="file" name="profile-picture">
                <button type="submit">Upload</button>
            </form>
            <form method="post">
                <button type="submit" name="remove-picture">Remove Picture</button>
            </form>
            <form method="post" action="logout.php">
                <button type="submit">Logout</button>
            </form>
        </div>

        <p>
            <?php echo $message; ?>
        </p>
</body>

</html>
