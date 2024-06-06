document.getElementById('showRegister').addEventListener('click', function (e) {
    e.preventDefault();
    document.getElementById('loginForm').classList.add('hidden');
    setTimeout(() => {
        document.getElementById('loginForm').style.display = 'none';
        document.getElementById('registerForm').style.display = 'block';
        setTimeout(() => {
            document.getElementById('registerForm').classList.remove('hidden');
        }, 20);
    }, 600);
});

document.getElementById('showLogin').addEventListener('click', function (e) {
    e.preventDefault();
    document.getElementById('registerForm').classList.add('hidden');
    setTimeout(() => {
        document.getElementById('registerForm').style.display = 'none';
        document.getElementById('loginForm').style.display = 'block';
        setTimeout(() => {
            document.getElementById('loginForm').classList.remove('hidden');
        }, 20);
    }, 600);
});
// After successful registration submission
if (xhr.status === 200) {
    // OTP verification successful, show OTP verification form
    document.getElementById('registerForm').style.display = 'none';
    document.getElementById('otpForm').style.display = 'block';
} else {
    // OTP verification failed, display error message
    alert('OTP verification failed. Please try again.');
}
