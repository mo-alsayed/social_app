<?php
/**
 * Main Feed Page: feed.php
 * Displays the post creation form and a timeline of all posts from all users.
 * Requires config.php for database connection and session management.
 */
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("location: index.php");
    exit;
}

// Store and clear session messages
$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);

// 1. Fetch All Posts (Joined with User Info)
$posts = [];
try {
    // Select posts and the associated username/profile info
    $stmt = $pdo->prepare("
        SELECT 
            p.*, 
            u.username, 
            u.full_name, 
            u.profile_picture_url 
        FROM posts p
        JOIN users u ON p.user_id = u.user_id
        ORDER BY p.created_at DESC
    ");
    $stmt->execute();
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Log the error but don't expose sensitive details to the user
    error_log("Feed fetching error: " . $e->getMessage());
    $error_message = "Could not load posts. Database error occurred.";
}

// Get current user's info for the post form area
$current_user_id = $_SESSION['user_id'];
$current_username = 'Unknown User'; // Fallback
try {
    $stmt_user = $pdo->prepare("SELECT username, profile_picture_url FROM users WHERE user_id = ?");
    $stmt_user->execute([$current_user_id]);
    $current_user_data = $stmt_user->fetch(PDO::FETCH_ASSOC);
    if ($current_user_data) {
        $current_username = htmlspecialchars($current_user_data['username']);
        $current_user_pic = htmlspecialchars($current_user_data['profile_picture_url'] ?: 'assets/images/default_avatar.png');
    }
} catch (PDOException $e) {
    // User data fetch failed, use fallbacks
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home Feed - SocialApp</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Base Styling */
        body {
            font-family: 'Inter', Arial, sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 600px;
            margin: 20px auto;
            padding: 0 10px;
        }

        /* Navigation */
        .navigation {
            background-color: #1877F2;
            padding: 10px 0;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .navigation .nav-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 600px;
            margin: 0 auto;
            padding: 0 10px;
        }

        .navigation a {
            color: white;
            text-decoration: none;
            font-weight: 600;
            padding: 5px 10px;
            border-radius: 4px;
            transition: background-color 0.3s;
        }

        .navigation a:hover {
            background-color: #0056b3;
        }

        /* Messages (Success/Error) */
        .message {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 500;
            text-align: center;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Post Form */
        .post-create-card {
            background-color: #fff;
            padding: 15px;
            border-radius: 12px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }

        .post-header {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }

        .post-header img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 10px;
            border: 2px solid #ddd;
        }

        .post-create-card textarea {
            width: 100%;
            min-height: 80px;
            border: none;
            resize: vertical;
            padding: 10px;
            border-radius: 6px;
            background-color: #f0f2f5;
            font-size: 1em;
            outline: none;
            box-sizing: border-box;
        }

        .post-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 10px;
            border-top: 1px solid #eee;
            margin-top: 10px;
        }

        .post-actions input[type="file"] {
            display: none;
        }

        .file-label {
            background-color: #e4e6eb;
            color: #1877F2;
            padding: 8px 15px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: background-color 0.2s;
        }

        .file-label:hover {
            background-color: #d8dade;
        }

        .post-actions button {
            background-color: #1877F2;
            color: white;
            padding: 8px 20px;
            border: none;
            border-radius: 6px;
            font-weight: 700;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .post-actions button:hover {
            background-color: #166FE5;
        }

        /* Post Feed */
        .post-card {
            background-color: #ffffff;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 15px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
            border: 1px solid #e0e0e0;
        }

        .post-user-info {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }

        .post-user-info img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 10px;
            border: 2px solid #ddd;
        }

        .post-user-details a {
            font-weight: 700;
            color: #1c1e21;
            text-decoration: none;
            font-size: 1.1em;
        }

        .post-user-details small {
            display: block;
            color: #606770;
            font-size: 0.85em;
        }

        /* Post Content and Media */
        .post-content-text {
            margin-bottom: 10px;
            white-space: pre-wrap;
            color: #1c1e21;
            line-height: 1.4;
        }

        .post-media {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin-top: 10px;
            display: block;
            object-fit: cover;
        }
    </style>
</head>

<body>
    <div class="navigation">
        <div class="nav-container">
            <div class="nav-links">
                <a href="feed.php">Home</a>
                <a href="profile.php">Profile</a>
            </div>
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="container">

        <?php if ($success_message): ?>
            <div class="message success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <!-- Post Creation Form -->
        <div class="post-create-card">
            <div class="post-header">
                <img src="<?php echo $current_user_pic; ?>" alt="Your Avatar"
                    onerror="this.onerror=null; this.src='https://placehold.co/40x40/007bff/ffffff?text=<?php echo substr($current_username, 0, 1); ?>'">
                <p style="font-weight: 600;"><?php echo $current_username; ?></p>
            </div>
            <!-- IMPORTANT: enctype="multipart/form-data" is required for file uploads -->
            <form action="api/post_handler.php" method="POST" enctype="multipart/form-data">
                <textarea name="post_content" placeholder="What's on your mind?"></textarea>
                <div class="post-actions">
                    <label for="post_media" class="file-label">
                        <input type="file" id="post_media" name="post_media" accept="image/jpeg,image/png,image/gif">
                        Add Photo/Video
                    </label>
                    <button type="submit">Post</button>
                </div>
            </form>
        </div>

        <!-- Timeline / Post List -->
        <?php if (empty($posts)): ?>
            <p style="text-align: center; color: #606770; padding: 20px; background-color: #fff; border-radius: 12px;">No
                posts to display. Be the first to post!</p>
        <?php else: ?>
            <?php foreach ($posts as $post): ?>
                <div class="post-card">
                    <div class="post-user-info">
                        <img src="<?php echo htmlspecialchars($post['profile_picture_url'] ?: 'assets/images/default_avatar.png'); ?>"
                            alt="<?php echo htmlspecialchars($post['username']); ?>'s Profile Picture"
                            onerror="this.onerror=null; this.src='https://placehold.co/40x40/007bff/ffffff?text=<?php echo substr(htmlspecialchars($post['username']), 0, 1); ?>'">
                        <div class="post-user-details">
                            <a href="profile.php?id=<?php echo htmlspecialchars($post['user_id']); ?>">
                                <?php echo htmlspecialchars($post['full_name'] ?: $post['username']); ?>
                            </a>
                            <small>@<?php echo htmlspecialchars($post['username']); ?> &middot;
                                <?php echo date('M j, Y \a\t g:i a', strtotime($post['created_at'])); ?></small>
                        </div>
                    </div>

                    <p class="post-content-text"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>

                    <?php if ($post['media_url']): ?>
                        <?php
                        // The path stored in the database is correct (e.g., uploads/post_123.jpg).
                        // Since feed.php is in the root directory, this path should work directly.
                        $ext = pathinfo($post['media_url'], PATHINFO_EXTENSION);
                        $is_image = in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'gif']);
                        ?>
                        <?php if ($is_image): ?>
                            <img src="<?php echo htmlspecialchars($post['media_url']); ?>" alt="Post Media" class="post-media">
                        <?php else: ?>
                            <a href="<?php echo htmlspecialchars($post['media_url']); ?>" target="_blank"
                                style="color: #1877F2; display: block; margin-top: 10px;">View Media Attachment</a>
                        <?php endif; ?>
                    <?php endif; ?>

                    <!-- Interaction options can go here (Like, Comment, etc.) -->
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>

</html>