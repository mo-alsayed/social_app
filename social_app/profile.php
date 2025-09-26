<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$current_user = getCurrentUser();

// Get profile user
$profile_id = isset($_GET['id']) ? intval($_GET['id']) : $current_user['id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$profile_id]);
$profile_user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$profile_user) {
    header("Location: index.php");
    exit();
}

// Get user's posts
$stmt = $pdo->prepare("
    SELECT p.*, u.username, u.profile_picture,
            COUNT(DISTINCT l.id) as like_count,
            COUNT(DISTINCT c.id) as comment_count,
            EXISTS(SELECT 1 FROM likes WHERE user_id = ? AND post_id = p.id) as user_liked
    FROM posts p 
    JOIN users u ON p.user_id = u.id 
    LEFT JOIN likes l ON p.id = l.post_id 
    LEFT JOIN comments c ON p.id = c.post_id 
    WHERE p.user_id = ?
    GROUP BY p.id 
    ORDER BY p.created_at DESC
");
$stmt->execute([$current_user['id'], $profile_id]);
$user_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get post count
$stmt = $pdo->prepare("SELECT COUNT(*) as post_count FROM posts WHERE user_id = ?");
$stmt->execute([$profile_id]);
$post_count = $stmt->fetch(PDO::FETCH_ASSOC)['post_count'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?= htmlspecialchars($profile_user['username']) ?> - SocialFeed
    </title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <h2>SocialFeed</h2>
            </div>
            <div class="nav-menu">
                <a href="index.php" class="nav-link"><i class="fas fa-home"></i> Home</a>
                <a href="profile.php?id=<?= $current_user['id'] ?>" class="nav-link active"><i class="fas fa-user"></i>
                    Profile</a>
                <a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="profile-container">
            <!-- Profile Header -->
            <div class="profile-header">
                <div class="cover-photo">
                    <img src="assets/images/covers/<?= $profile_user['cover_picture'] ?>" alt="Cover photo">
                    <?php if ($profile_user['id'] == $current_user['id']): ?>
                        <button class="edit-cover-btn"><i class="fas fa-camera"></i> Edit Cover</button>
                    <?php endif; ?>
                </div>

                <div class="profile-info">
                    <div class="profile-avatar">
                        <img src="assets/images/profiles/<?= $profile_user['profile_picture'] ?>" alt="Profile picture">
                        <?php if ($profile_user['id'] == $current_user['id']): ?>
                            <button class="edit-avatar-btn"><i class="fas fa-camera"></i></button>
                        <?php endif; ?>
                    </div>

                    <div class="profile-details">
                        <h1>
                            <?= htmlspecialchars($profile_user['full_name'] ?? $profile_user['username']) ?>
                        </h1>
                        <p class="username">@
                            <?= htmlspecialchars($profile_user['username']) ?>
                        </p>
                        <?php if ($profile_user['bio']): ?>
                            <p class="bio">
                                <?= nl2br(htmlspecialchars($profile_user['bio'])) ?>
                            </p>
                        <?php endif; ?>

                        <div class="profile-stats">
                            <div class="stat">
                                <span class="stat-number">
                                    <?= $post_count ?>
                                </span>
                                <span class="stat-label">Posts</span>
                            </div>
                            <div class="stat">
                                <span class="stat-number">0</span>
                                <span class="stat-label">Followers</span>
                            </div>
                            <div class="stat">
                                <span class="stat-number">0</span>
                                <span class="stat-label">Following</span>
                            </div>
                        </div>
                    </div>

                    <?php if ($profile_user['id'] == $current_user['id']): ?>
                        <button class="edit-profile-btn">Edit Profile</button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- User Posts -->
            <div class="profile-posts">
                <h3>Posts</h3>
                <div id="postsContainer">
                    <?php foreach ($user_posts as $post): ?>
                        <div class="card post-card" data-post-id="<?= $post['id'] ?>">
                            <div class="post-header">
                                <img src="assets/images/profiles/<?= $post['profile_picture'] ?>" alt="Profile"
                                    class="post-avatar">
                                <div class="post-user-info">
                                    <h4>
                                        <?= htmlspecialchars($post['username']) ?>
                                    </h4>
                                    <span class="post-time">
                                        <?= time_elapsed_string($post['created_at']) ?>
                                    </span>
                                </div>
                            </div>

                            <div class="post-content">
                                <p>
                                    <?= nl2br(htmlspecialchars($post['content'])) ?>
                                </p>
                                <?php if ($post['media_type'] != 'none'): ?>
                                    <div class="post-media">
                                        <?php if ($post['media_type'] == 'image'): ?>
                                            <img src="assets/uploads/<?= $post['media_url'] ?>" alt="Post image">
                                        <?php elseif ($post['media_type'] == 'video'): ?>
                                            <video controls>
                                                <source src="assets/uploads/<?= $post['media_url'] ?>" type="video/mp4">
                                            </video>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="post-stats">
                                <span class="like-count">
                                    <?= $post['like_count'] ?> likes
                                </span>
                                <span class="comment-count">
                                    <?= $post['comment_count'] ?> comments
                                </span>
                            </div>

                            <div class="post-actions">
                                <button class="like-btn <?= $post['user_liked'] ? 'liked' : '' ?>"
                                    onclick="toggleLike(<?= $post['id'] ?>)">
                                    <i class="fas fa-heart"></i> Like
                                </button>
                                <button class="comment-btn" onclick="focusComment(<?= $post['id'] ?>)">
                                    <i class="fas fa-comment"></i> Comment
                                </button>
                            </div>

                            <div class="comments-section">
                                <div class="comments-list" id="comments-<?= $post['id'] ?>">
                                    <!-- Comments will be loaded here -->
                                </div>
                                <div class="comment-form">
                                    <input type="text" class="comment-input" placeholder="Write a comment..."
                                        onkeypress="handleCommentKeypress(event, <?= $post['id'] ?>)">
                                    <button class="comment-submit" onclick="addComment(<?= $post['id'] ?>)">Post</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
</body>

</html>

<?php
function time_elapsed_string($datetime, $full = false)
{
    // Same function as in index.php
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );

    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full)
        $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}
?>