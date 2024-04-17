function showLoginModal() {
    document.getElementById('loginModal').style.display = 'block';
}

function closeLoginModal() {
    document.getElementById('loginModal').style.display = 'none';
}

function showSignUpModal() {
    document.getElementById('signupModal').style.display = 'block';
}

function closeSignUpModal() {
    document.getElementById('signupModal').style.display = 'none';
    
}function login() {
    var formData = new FormData(document.getElementById('loginForm'));
    
    fetch('inc/auth.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Login successful');
            window.location.reload(); // Reload the page after successful login
        } else {
            alert(data.message); // Display error message
        }
    })
    .catch(error => console.error('Error:', error));
}
// JavaScript function to handle logout
function logout() {
    fetch('inc/logout.php')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update UI to show login and sign-up links, hide logout link
            document.getElementById('nav-links').innerHTML = `
                <a href="#" onclick="showLoginModal()">Login</a>
                <a href="#" onclick="showSignUpModal()">Sign Up</a>
            `;
            // Optionally, you can reset the page
            window.location.href = 'index.php';
        } else {
            alert(data.message); // Display error message if logout fails
        }
    })
    .catch(error => console.error('Error:', error));
}
function uploadImage() {
    var fileInput = document.getElementById("imageInput");
    var file = fileInput.files[0];
    var formData = new FormData();
    formData.append("image", file);

    var xhr = new XMLHttpRequest();
    xhr.open("POST", "upload.php", true);
    xhr.onload = function() {
        if (xhr.status === 200) {
            console.log("Image uploaded successfully.");
        } else {
            console.error("Error uploading image.");
        }
    };
    xhr.send(formData);
}
function submitRecipe() {
    var title = document.getElementById('title').value;
    var content = document.getElementById('content').value;
    var fileInput = document.getElementById('recipeImage');
    var file = fileInput.files[0];
    var formData = new FormData();

    formData.append('title', title);
    formData.append('content', content);
    formData.append('recipeImage', file);

    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'profile.php', true);
    xhr.onload = function() {
        if (xhr.status === 200) {
            alert('Recipe created successfully.');
            location.reload(); // Refresh the page after successful upload
        } else {
            alert('Error creating recipe.');
        }
    };
    xhr.send(formData);
}
function submitSignUp() {
    var formData = new FormData(document.getElementById('signupForm'));

    fetch('functions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            closeSignUpModal();
            // Optionally, you can perform additional actions after successful sign-up
        } else {
            alert(data.message);
            // Optionally, you can handle the failure scenario
        }
    })
    .catch(error => console.error('Error:', error));
}
function toggleCategory(categoryId) {
    let currentUrl = window.location.href;
    let categoryParam = 'category=' + categoryId;
    
    // Check if category is already in URL
    if (currentUrl.includes(categoryParam)) {
        // Remove category from URL
        currentUrl = currentUrl.replace(categoryParam, '');
        // If there's no other category, remove the '?' as well
        if (!currentUrl.includes('category=')) {
            currentUrl = currentUrl.replace('?', ''); // Remove the '?' from the URL
        }
    } else {
        // Add category to URL
        if (currentUrl.includes('?')) {
            currentUrl += '&' + categoryParam;
        } else {
            currentUrl += '?' + categoryParam;
        }
    }
    
    // Reload page with updated URL
    window.location.href = currentUrl;
}$(document).ready(function() {
    $('.like-button').on('click', function() {
        var commentId = $(this).data('comment-id');
        var button = $(this);

        // Check if the button is currently liked or unliked
        var isLiked = button.hasClass('liked');

        // Determine which action to take based on the current state
        var action = isLiked ? 'unlike_comment_id' : 'like_comment_id';

        $.post("<?php echo $_SERVER['PHP_SELF']; ?>", { action: action, comment_id: commentId }, function(data) {
            var response = JSON.parse(data);
            $('#like-count-' + commentId).text(response.like_count + ' likes');  
            // Toggle class and button text based on the current state
            if (isLiked) {
                button.removeClass('liked');
                button.text('Like');
            } else {
                button.addClass('liked');
                button.text('Unlike');
            }
        }).fail(function() {
            alert('Error processing like/unlike.');
        });
    });
});
