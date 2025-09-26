<?php
require_once 'config.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

header('Content-Type: application/json');
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'create_post':
            $content = trim($_POST['content'] ?? '');
            $media_type = 'none';
            $media_url = '';

            // Handle file upload
            if (isset($_FILES['media']) && $_FILES['media']['error'] == 0) {
                $file = $_FILES['media'];
                $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowed_image_ext = ['jpg', 'jpeg', 'png', 'gif'];
                $allowed_video_ext = ['mp4', 'mov', 'avi'];

                if (in_array($file_ext, $allowed_image_ext)) {
                    $media_type = 'image';
                } elseif (in_array($file_ext, $allowed_video_ext)) {
                    $media_type = 'video';
                } else {
                    echo json_encode(['error' => 'Invalid file type']);
                    exit();
                }

                $media_url = uniqid() . '.' . $file_ext;
                move_uploaded_file($file['tmp_name'], 'assets/uploads/' . $media_url);
            }

            $stmt = $pdo->prepare("INSERT INTO posts (user_id, content, media_url, media_type) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$user_id, $content, $media_url, $media_type])) {
                $post_id = $pdo->lastInsertId();

                // Get the new post with user info
                $stmt = $pdo->prepare("
                    SELECT p.*, u.username, u.profile_picture 
                    FROM posts p 
                    JOIN users u ON p.user_id = u.id 
                    WHERE p.id = ?
                ");
                $stmt->execute([$post_id]);
                $post = $stmt->fetch(PDO::FETCH_ASSOC);

                echo json_encode(['success' => true, 'post' => $post]);
            } else {
                echo json_encode(['error' => 'Failed to create post']);
            }
            break;

        case 'toggle_like':
            $post_id = intval($_POST['post_id']);

            // Check if already liked
            $stmt = $pdo->prepare("SELECT id FROM likes WHERE user_id = ? AND post_id = ?");
            $stmt->execute([$user_id, $post_id]);

            if ($stmt->rowCount() > 0) {
                // Unlike
                $stmt = $pdo->prepare("DELETE FROM likes WHERE user_id = ? AND post_id = ?");
                $stmt->execute([$user_id, $post_id]);
                echo json_encode(['liked' => false]);
            } else {
                // Like
                $stmt = $pdo->prepare("INSERT INTO likes (user_id, post_id) VALUES (?, ?)");
                $stmt->execute([$user_id, $post_id]);
                echo json_encode(['liked' => true]);
            }
            break;

        case 'add_comment':
            $post_id = intval($_POST['post_id']);
            $content = trim($_POST['content']);

            if (empty($content)) {
                echo json_encode(['error' => 'Comment cannot be empty']);
                exit();
            }

            $stmt = $pdo->prepare("INSERT INTO comments (user_id, post_id, content) VALUES (?, ?, ?)");
            if ($stmt->execute([$user_id, $post_id, $content])) {
                $comment_id = $pdo->lastInsertId();

                // Get the new comment with user info
                $stmt = $pdo->prepare("
                    SELECT c.*, u.username, u.profile_picture 
                    FROM comments c 
                    JOIN users u ON c.user_id = u.id 
                    WHERE c.id = ?
                ");
                $stmt->execute([$comment_id]);
                $comment = $stmt->fetch(PDO::FETCH_ASSOC);

                echo json_encode(['success' => true, 'comment' => $comment]);
            } else {
                echo json_encode(['error' => 'Failed to add comment']);
            }
            break;

        case 'get_comments':
            $post_id = intval($_POST['post_id']);

            $stmt = $pdo->prepare("
                SELECT c.*, u.username, u.profile_picture 
                FROM comments c 
                JOIN users u ON c.user_id = u.id 
                WHERE c.post_id = ? 
                ORDER BY c.created_at ASC
            ");
            $stmt->execute([$post_id]);
            $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['comments' => $comments]);
            break;

        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}
?>