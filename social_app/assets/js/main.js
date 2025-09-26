// DOM Content Loaded
document.addEventListener('DOMContentLoaded', function() {
    // Load comments for all posts on page load
    document.querySelectorAll('.post-card').forEach(post => {
        const postId = post.dataset.postId;
        loadComments(postId);
    });
});

// Create Post
document.getElementById('createPostForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'create_post');
    
    fetch('ajax_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Clear form
            this.reset();
            document.getElementById('mediaPreview').innerHTML = '';
            
            // Add new post to feed
            addPostToFeed(data.post);
            
            // Show success message
            showNotification('Post created successfully!', 'success');
        } else {
            showNotification(data.error || 'Failed to create post', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
    });
});

// Media upload preview
document.getElementById('mediaUpload')?.addEventListener('change', function(e) {
    const file = this.files[0];
    const preview = document.getElementById('mediaPreview');
    
    if (file) {
        preview.innerHTML = '';
        
        if (file.type.startsWith('image/')) {
            const img = document.createElement('img');
            img.src = URL.createObjectURL(file);
            preview.appendChild(img);
        } else if (file.type.startsWith('video/')) {
            const video = document.createElement('video');
            video.controls = true;
            video.src = URL.createObjectURL(file);
            preview.appendChild(video);
        }
    }
});

// Toggle Like
function toggleLike(postId) {
    const formData = new FormData();
    formData.append('action', 'toggle_like');
    formData.append('post_id', postId);
    
    fetch('ajax_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            showNotification(data.error, 'error');
            return;
        }
        
        const likeBtn = document.querySelector(`.post-card[data-post-id="${postId}"] .like-btn`);
        const likeCount = document.querySelector(`.post-card[data-post-id="${postId}"] .like-count`);
        
        if (data.liked) {
            likeBtn.classList.add('liked');
            likeBtn.innerHTML = '<i class="fas fa-heart"></i> Liked';
            
            // Update like count
            const currentCount = parseInt(likeCount.textContent) || 0;
            likeCount.textContent = (currentCount + 1) + ' likes';
        } else {
            likeBtn.classList.remove('liked');
            likeBtn.innerHTML = '<i class="fas fa-heart"></i> Like';
            
            // Update like count
            const currentCount = parseInt(likeCount.textContent) || 1;
            likeCount.textContent = (currentCount - 1) + ' likes';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
    });
}

// Add Comment
function addComment(postId) {
    const input = document.querySelector(`.post-card[data-post-id="${postId}"] .comment-input`);
    const content = input.value.trim();
    
    if (!content) return;
    
    const formData = new FormData();
    formData.append('action', 'add_comment');
    formData.append('post_id', postId);
    formData.append('content', content);
    
    fetch('ajax_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Clear input
            input.value = '';
            
            // Add comment to list
            addCommentToList(postId, data.comment);
            
            // Update comment count
            const commentCount = document.querySelector(`.post-card[data-post-id="${postId}"] .comment-count`);
            const currentCount = parseInt(commentCount.textContent) || 0;
            commentCount.textContent = (currentCount + 1) + ' comments';
        } else {
            showNotification(data.error || 'Failed to add comment', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
    });
}

// Load Comments
function loadComments(postId) {
    const formData = new FormData();
    formData.append('action', 'get_comments');
    formData.append('post_id', postId);
    
    fetch('ajax_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.comments) {
            const commentsList = document.getElementById(`comments-${postId}`);
            commentsList.innerHTML = '';
            
            data.comments.forEach(comment => {
                addCommentToList(postId, comment);
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

// Add Comment to List
function addCommentToList(postId, comment) {
    const commentsList = document.getElementById(`comments-${postId}`);
    
    const commentDiv = document.createElement('div');
    commentDiv.className = 'comment';
    commentDiv.innerHTML = `
        <img src="assets/images/profiles/${comment.profile_picture}" alt="Avatar" class="comment-avatar">
        <div class="comment-content">
            <div class="comment-user">${escapeHtml(comment.username)}</div>
            <div class="comment-text">${escapeHtml(comment.content)}</div>
        </div>
    `;
    
    commentsList.appendChild(commentDiv);
    commentsList.scrollTop = commentsList.scrollHeight;
}

// Focus Comment Input
function focusComment(postId) {
    const input = document.querySelector(`.post-card[data-post-id="${postId}"] .comment-input`);
    input.focus();
}

// Handle Enter Key in Comment Input
function handleCommentKeypress(e, postId) {
    if (e.key === 'Enter') {
        addComment(postId);
    }
}

// Add Post to Feed
function addPostToFeed(post) {
    const postsContainer = document.getElementById('postsContainer');
    const timeAgo = getTimeAgo(post.created_at);
    
    const postDiv = document.createElement('div');
    postDiv.className = 'card post-card';
    postDiv.dataset.postId = post.id;
    postDiv.innerHTML = `
        <div class="post-header">
            <img src="assets/images/profiles/${post.profile_picture}" alt="Profile" class="post-avatar">
            <div class="post-user-info">
                <h4>${escapeHtml(post.username)}</h4>
                <span class="post-time">${timeAgo}</span>
            </div>
        </div>
        
        <div class="post-content">
            <p>${escapeHtml(post.content)}</p>
            ${post.media_type !== 'none' ? `
                <div class="post-media">
                    ${post.media_type === 'image' ? 
                        `<img src="assets/uploads/${post.media_url}" alt="Post image">` : 
                        `<video controls><source src="assets/uploads/${post.media_url}" type="video/mp4"></video>`
                    }
                </div>
            ` : ''}
        </div>
        
        <div class="post-stats">
            <span class="like-count">0 likes</span>
            <span class="comment-count">0 comments</span>
        </div>
        
        <div class="post-actions">
            <button class="like-btn" onclick="toggleLike(${post.id})">
                <i class="fas fa-heart"></i> Like
            </button>
            <button class="comment-btn" onclick="focusComment(${post.id})">
                <i class="fas fa-comment"></i> Comment
            </button>
        </div>
        
        <div class="comments-section">
            <div class="comments-list" id="comments-${post.id}"></div>
            <div class="comment-form">
                <input type="text" class="comment-input" placeholder="Write a comment..." 
                       onkeypress="handleCommentKeypress(event, ${post.id})">
                <button class="comment-submit" onclick="addComment(${post.id})">Post</button>
            </div>
        </div>
    `;
    
    // Insert at the top of the feed
    if (postsContainer.firstChild) {
        postsContainer.insertBefore(postDiv, postsContainer.firstChild.nextSibling);
    } else {
        postsContainer.appendChild(postDiv);
    }
    
    // Load comments for the new post
    loadComments(post.id);
}

// Utility Functions
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function getTimeAgo(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now - date;
    const diffSecs = Math.floor(diffMs / 1000);
    const diffMins = Math.floor(diffSecs / 60);
    const diffHours = Math.floor(diffMins / 60);
    const diffDays = Math.floor(diffHours / 24);
    
    if (diffSecs < 60) return 'just now';
    if (diffMins < 60) return `${diffMins} minute${diffMins > 1 ? 's' : ''} ago`;
    if (diffHours < 24) return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`;
    if (diffDays < 7) return `${diffDays} day${diffDays > 1 ? 's' : ''} ago`;
    
    return date.toLocaleDateString();
}

function showNotification(message, type) {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 4px;
        color: white;
        font-weight: 500;
        z-index: 10000;
        transition: transform 0.3s, opacity 0.3s;
        transform: translateX(100%);
        opacity: 0;
    `;
    
    if (type === 'success') {
        notification.style.backgroundColor = '#27ae60';
    } else {
        notification.style.backgroundColor = '#e74c3c';
    }
    
    document.body.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
        notification.style.opacity = '1';
    }, 100);
    
    // Remove after 3 seconds
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        notification.style.opacity = '0';
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 3000);
}
// Add to your main.js or in a script tag
document.addEventListener('DOMContentLoaded', function() {
    // Handle broken images
    document.querySelectorAll('img').forEach(img => {
        img.addEventListener('error', function() {
            if (this.src.includes('profiles')) {
                this.src = 'assets/images/profiles/default_profile.jpg';
            } else if (this.src.includes('covers')) {
                this.src = 'assets/images/covers/default_cover.jpg';
            }
        });
    });
});