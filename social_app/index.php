<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user = getCurrentUser();

// Get posts for feed
$stmt = $pdo->prepare("
    SELECT p.*, u.username, u.profile_picture, 
           COUNT(DISTINCT l.id) as like_count,
           COUNT(DISTINCT c.id) as comment_count,
           EXISTS(SELECT 1 FROM likes WHERE user_id = ? AND post_id = p.id) as user_liked
    FROM posts p 
    JOIN users u ON p.user_id = u.id 
    LEFT JOIN likes l ON p.id = l.post_id 
    LEFT JOIN comments c ON p.id = c.post_id 
    GROUP BY p.id 
    ORDER BY p.created_at DESC
");
$stmt->execute([$user['id']]);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SocialFeed - Home</title>
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
                <a href="index.php" class="nav-link active"><i class="fas fa-home"></i> Home</a>
                <a href="profile.php?id=<?= $user['id'] ?>" class="nav-link"><i class="fas fa-user"></i> Profile</a>
                <a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="feed-container">
            <!-- Create Post Card -->
            <div class="card create-post-card">
                <div class="card-header">
                    <h3>Create Post</h3>
                </div>
                <div class="card-body">
                    <form id="createPostForm" enctype="multipart/form-data">
                        <textarea name="content" placeholder="What's on your mind?" class="post-textarea"></textarea>
                        <div class="post-actions">
                            <label for="mediaUpload" class="media-btn">
                                <i class="fas fa-image"></i> Photo/Video
                            </label>
                            <input type="file" id="mediaUpload" name="media" accept="image/*,video/*"
                                style="display: none;">
                            <button type="submit" class="post-btn">Post</button>
                        </div>
                        <div id="mediaPreview" class="media-preview"></div>
                    </form>
                </div>
            </div>

            <!-- Posts Feed -->
            <div id="postsContainer">
                <?php foreach ($posts as $post): ?>
                    <div class="card post-card" data-post-id="<?= $post['id'] ?>">
                        <div class="post-header">
                            <img src="<?= getSafeImage('assets/images/profiles/' . $post['profile_picture']) ?>"
                                alt="Profile" class="post-avatar">
                            <div class="post-user-info">
                                <h4><?= htmlspecialchars($post['username']) ?></h4>
                                <span class="post-time"><?= time_elapsed_string($post['created_at']) ?></span>
                            </div>
                        </div>

                        <div class="post-content">
                            <p><?= nl2br(htmlspecialchars($post['content'])) ?></p>
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
                            <span class="like-count"><?= $post['like_count'] ?> likes</span>
                            <span class="comment-count"><?= $post['comment_count'] ?> comments</span>
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

    <script src="assets/js/main.js"></script>
</body>

</html>

<?php
function time_elapsed_string($datetime, $full = false)
{
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