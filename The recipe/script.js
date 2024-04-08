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
