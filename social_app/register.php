<?php
require_once 'config.php';

if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = trim($_POST['full_name']);

    $profile_picture = 'default_profile.jpg';
    $cover_picture = 'default_cover.jpg';

    // Validation
    if (empty($username) || empty($email) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } else {
        // Check if username or email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);

        if ($stmt->rowCount() > 0) {
            $error = 'Username or email already exists.';
        } else {
            // Handle profile picture upload
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
                $profile_result = handleImageUpload($_FILES['profile_picture'], 'profile');
                if ($profile_result['success']) {
                    $profile_picture = $profile_result['filename'];
                } else {
                    $error = $profile_result['error'];
                }
            }

            // Handle cover picture upload
            if (empty($error) && isset($_FILES['cover_picture']) && $_FILES['cover_picture']['error'] == 0) {
                $cover_result = handleImageUpload($_FILES['cover_picture'], 'cover');
                if ($cover_result['success']) {
                    $cover_picture = $cover_result['filename'];
                } else {
                    $error = $cover_result['error'];
                }
            }

            if (empty($error)) {
                // Create user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, profile_picture, cover_picture) VALUES (?, ?, ?, ?, ?, ?)");

                if ($stmt->execute([$username, $email, $hashed_password, $full_name, $profile_picture, $cover_picture])) {
                    $success = 'Registration successful! You can now login.';

                    // Clear form
                    $_POST = array();
                } else {
                    $error = 'Registration failed. Please try again.';
                }
            }
        }
    }
}

// Function to handle image upload
function handleImageUpload($file, $type)
{
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
    $max_size = 5 * 1024 * 1024; // 5MB

    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    // Validate file type
    if (!in_array($file_extension, $allowed_extensions)) {
        return ['success' => false, 'error' => 'Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.'];
    }

    // Validate file size
    if ($file['size'] > $max_size) {
        return ['success' => false, 'error' => 'File size too large. Maximum size is 5MB.'];
    }

    // Generate unique filename
    $filename = $type . '_' . uniqid() . '.' . $file_extension;
    $upload_path = 'assets/images/' . ($type == 'profile' ? 'profiles' : 'covers') . '/' . $filename;

    // Create directory if it doesn't exist
    $directory = 'assets/images/' . ($type == 'profile' ? 'profiles' : 'covers');
    if (!is_dir($directory)) {
        mkdir($directory, 0755, true);
    }

    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        return ['success' => true, 'filename' => $filename];
    } else {
        return ['success' => false, 'error' => 'Failed to upload file.'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SocialFeed - Register</title>
    <link rel="stylesheet" href="assets/css/auth.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h2>Join SocialFeed</h2>
                <p>Create your account</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= $error ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>

            <form method="POST" class="auth-form" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name"
                        value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="username">Username *</label>
                    <input type="text" id="username" name="username" required
                        value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" required
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>

                <!-- Profile Picture Upload -->
                <div class="form-group">
                    <label for="profile_picture">Profile Picture</label>
                    <div class="file-upload-container">
                        <div class="file-upload-preview">
                            <img id="profilePreview" src="assets/images/profiles/default_profile.png"
                                alt="Profile preview">
                        </div>
                        <label for="profile_picture" class="file-upload-btn">
                            <i class="fas fa-camera"></i> Choose Profile Picture
                        </label>
                        <input type="file" id="profile_picture" name="profile_picture" accept="image/*"
                            onchange="previewImage(this, 'profilePreview')">
                    </div>
                </div>

                <!-- Cover Picture Upload -->
                <div class="form-group">
                    <label for="cover_picture">Cover Picture</label>
                    <div class="file-upload-container">
                        <div class="file-upload-preview cover-preview">
                            <img id="coverPreview" src="assets/images/covers/default_cover.jpg" alt="Cover preview">
                        </div>
                        <label for="cover_picture" class="file-upload-btn">
                            <i class="fas fa-image"></i> Choose Cover Picture
                        </label>
                        <input type="file" id="cover_picture" name="cover_picture" accept="image/*"
                            onchange="previewImage(this, 'coverPreview')">
                    </div>
                </div>

                <button type="submit" class="auth-btn">Register</button>
            </form>

            <div class="auth-footer">
                <p>Already have an account? <a href="login.php">Login here</a></p>
            </div>
        </div>
    </div>

    <script>
        function previewImage(input, previewId) {
            const preview = document.getElementById(previewId);
            const file = input.files[0];

            if (file) {
                const reader = new FileReader();

                reader.onload = function (e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }

                reader.readAsDataURL(file);
            } else {
                preview.src = previewId === 'profilePreview'
                    ? 'assets/images/profiles/default_profile.jpg'
                    : 'assets/images/covers/default_cover.jpg';
            }
        }

        // Validate file size before upload
        document.getElementById('profile_picture').addEventListener('change', function (e) {
            validateFileSize(this, 5); // 5MB limit
        });

        document.getElementById('cover_picture').addEventListener('change', function (e) {
            validateFileSize(this, 5); // 5MB limit
        });

        function validateFileSize(input, maxSizeMB) {
            if (input.files[0] && input.files[0].size > maxSizeMB * 1024 * 1024) {
                alert(`File size must be less than ${maxSizeMB}MB`);
                input.value = '';
            }
        }
    </script>
</body>

</html>