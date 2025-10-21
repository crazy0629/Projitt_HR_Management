<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Set up your Account - Projitt</title>
  <meta name="viewport" content="width=500, initial-scale=1">
  <link href="https://fonts.googleapis.com/css2?family=Inter&display=swap" rel="stylesheet">
  <style>
    html, body, *, *:before, *:after {
      box-sizing: border-box;
    }
    body {
      margin: 0;
      padding: 0;
      background: #f7f7f7;
      font-family: 'Inter', Arial, sans-serif;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      min-width: 100vw;
    }
    .container {
      width: 100%;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      gap: 30px;
    }
    .logo {
      padding-top: 60px;
      display: flex;
      justify-content: center;
      width: 100%;
    }
    .logo img {
      height: 48px;
    }
    .center {
      width: 100%;
      flex: 1;
      display: flex;
      justify-content: center;
      align-items: center;
      padding-bottom: 10px;
    }
    .form-box {
      width: 482px;
      border: 1px solid #e9e9e9;
      border-radius: 16px;
      background: #fff;
      padding: 40px;
      box-sizing: border-box;
    }
    h1 {
      font-size: 22px;
      line-height: 30px;
      font-weight: 600;
      color: #353535;
      margin: 0;
    }
    .subtitle {
      font-size: 14px;
      line-height: 20px;
      margin-top: 10px;
      color: #4B4B4B;
    }
    label, .label {
      font-size: 14px;
      line-height: 22px;
      font-weight: 400;
      color: #353535;
      display: block;
      margin-top: 18px;
    }
    input[type="email"], input[type="password"], input[type="text"] {
      width: 100%;
      height: 48px;
      margin-top: 10px;
      padding: 0 12px;
      border: 1px solid #e9e9e9;
      border-radius: 8px;
      font-size: 16px;
      box-sizing: border-box;
      background: #f7f7f7;
    }
    input[type="email"]:hover,
    input[type="password"]:hover,
    input[type="text"]:hover,
    input[type="email"]:focus,
    input[type="password"]:focus,
    input[type="text"]:focus {
      border-color: #0d978b; /* Use your desired color */
      outline: none; /* Optional: removes the default blue outline */
    }
    input[hover] {
      border: 1px solid #0d978b;
    }
    input[disabled] {
      background: #f0f0f0;
      color: #aaa;
    }
    .relative {
      position: relative;
    }
    .icon-btn {
      position: absolute;
      right: 8px;
      top: 50%;
      transform: translateY(-50%);
      height: 28px;
      width: 28px;
      background: transparent;
      border: none;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 0;
    }
    .icon-btn img, .icon-btn svg {
      width: 15px;
      height: 15px;
    }
    .submit-btn {
      width: 100%;
      height: 48px;
      font-size: 14px;
      line-height: 20px;
      font-weight: 600;
      background: #0d978b;
      color: #fff;
      border: none;
      border-radius: 8px;
      margin-top: 24px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      transition: background 0.2s;
    }
    .submit-btn:hover {
      background: #086159;
    }
    .submit-btn:disabled {
      background: #aaa;
    }
    .form-message {
      color: #e53e3e;
      font-size: 13px;
      margin-top: 6px;
      min-height: 18px;
    }
    .success-message {
      color: #38a169;
      font-size: 13px;
      margin-top: 6px;
      min-height: 18px;
    }
    .footer {
      width: 100%;
    }
    .poweredby {
      width: 100%;
      display: flex;
      justify-content: center;
      margin-bottom: 0;
    }
    .poweredby img {
      height: 28px;
    }
    .footer-bottom {
      width: 100%;
      padding-bottom: 50px;
      padding-left: 80px;
      padding-right: 80px;
      display: flex;
      flex-direction: row;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      margin-top: 0;
      flex-wrap: wrap;
    }
    .footer-links {
      display: flex;
      gap: 16px;
      align-items: center;
    }
    .footer-link {
      font-size: 14px;
      line-height: 22px;
      text-decoration: underline;
      color: #a19e9e;
      cursor: pointer;
    }
    .footer-divider {
      width: 1px;
      height: 20px;
      background: #a19e9e;
    }
    .footer-copyright {
      font-size: 14px;
      line-height: 22px;
      color: #a19e9e;
    }
    @media (max-width: 600px) {
      .form-box {
        width: 100%;
        padding: 20px;
      }
      .footer-bottom {
        flex-direction: column;
        padding-left: 10px;
        padding-right: 10px;
        gap: 10px;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="logo">
      <img src="/images/logo.png" alt="logo">
    </div>
    <div class="center">
      <div class="form-box">
        <!-- Display Laravel session messages and errors -->
        @if (session('success'))
          <p class="success-message">{{ session('success') }}</p>
        @endif
        @if (session('error'))
          <p class="form-message">{{ session('error') }}</p>
        @endif
        @if ($errors && $errors->any())
          <div class="form-message">
            <ul>
              @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
        @endif

        <form action="{{ route('password.update') }}" method="POST" id="resetPasswordForm">
          @csrf
          <input type="hidden" name="token" value="{{ $token }}">
          <div>
            <h1>Set up your Account</h1>
            <p class="subtitle">Create your login credentials to get started with Projitt.</p>
          </div>

          <label class="label" for="email">Email Address</label>
          <input type="email" id="email" name="email" value="{{ old('email', $email) }}" required>

          <label class="label" for="password">New Password</label>
          <div class="relative">
            <input type="password" id="password" name="password" placeholder="Enter new password" required>
            <button type="button" class="icon-btn" id="togglePassword">
              <img src="{{ asset('images/icons/eye.svg') }}" alt="Show password" id="eyeOpen" style="display:block;">
              <img src="{{ asset('images/icons/eyeoff.svg') }}" alt="Hide password" id="eyeOff" style="display:none;">
            </button>
          </div>

          <label class="label" for="password_confirmation">Confirm New Password</label>
          <div class="relative">
            <input type="password" id="password_confirmation" name="password_confirmation" placeholder="Confirm new password" required>
            <button type="button" class="icon-btn" id="toggleConfirmPassword">
              <img src="/images/icons/eye.svg" alt="Show password" id="eyeOpenConfirm" style="display:block;">
              <img src="/images/icons/eyeoff.svg" alt="Hide password" id="eyeOffConfirm" style="display:none;">
            </button>
          </div>

          <button type="submit" class="submit-btn">
            <span id="loader" style="display:none;">
              <svg class="size-4 animate-spin" width="18" height="18" viewBox="0 0 24 24" fill="none">
                <circle cx="12" cy="12" r="10" stroke="#fff" stroke-width="4" opacity="0.2"/>
                <path d="M22 12a10 10 0 0 1-10 10" stroke="#fff" stroke-width="4" stroke-linecap="round"/>
              </svg>
            </span>
            <span id="submitText">Confirm</span>
          </button>
        </form>
      </div>
    </div>
    <div class="footer">
      <div class="poweredby">
        <img src="/images/poweredBy.png" alt="powered by">
      </div>
      <div class="footer-bottom">
        <div class="footer-links">
          <span class="footer-link">Terms of Service</span>
          <div class="footer-divider"></div>
          <span class="footer-link">Privacy Policy</span>
        </div>
        <span class="footer-copyright">© 2025 Projitt</span>
      </div>
    </div>
  </div>
</body>
</html>


<script>
  // Password toggle setup
  const passwordInput = document.getElementById('password');
  const togglePassword = document.getElementById('togglePassword');
  const eyeOpen = document.getElementById('eyeOpen');
  const eyeOff = document.getElementById('eyeOff');

  if (togglePassword) {
    togglePassword.addEventListener('click', () => {
      const isPassword = passwordInput.type === 'password';
      passwordInput.type = isPassword ? 'text' : 'password';
      eyeOpen.style.display = isPassword ? 'none' : 'block';
      eyeOff.style.display = isPassword ? 'block' : 'none';
    });
  }

  // Confirm password toggle setup
  const confirmPasswordInput = document.getElementById('password_confirmation');
  const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
  const eyeOpenConfirm = document.getElementById('eyeOpenConfirm');
  const eyeOffConfirm = document.getElementById('eyeOffConfirm');

  if (toggleConfirmPassword) {
    toggleConfirmPassword.addEventListener('click', () => {
      const isPassword = confirmPasswordInput.type === 'password';
      confirmPasswordInput.type = isPassword ? 'text' : 'password';
      eyeOpenConfirm.style.display = isPassword ? 'none' : 'block';
      eyeOffConfirm.style.display = isPassword ? 'block' : 'none';
    });
  }

  // Form & error/success setup
  // const form = document.getElementById('resetPasswordForm');
  const submitBtn = form.querySelector('.submit-btn');
  const loader = document.getElementById('loader');
  const submitText = document.getElementById('submitText');

  const passwordError = document.createElement('div');
  passwordError.className = 'form-message';
  passwordInput.parentNode.appendChild(passwordError);

  const confirmPasswordError = document.createElement('div');
  confirmPasswordError.className = 'form-message';
  confirmPasswordInput.parentNode.appendChild(confirmPasswordError);

  const successMessage = document.createElement('div');
  successMessage.className = 'success-message';
  submitBtn.parentNode.insertBefore(successMessage, submitBtn.nextSibling);

  // Form submission handler
   document.getElementById('resetPasswordForm').addEventListener('submit', function(event) {
    event.preventDefault();

    // Reset messages
    passwordError.textContent = '';
    confirmPasswordError.textContent = '';
    successMessage.textContent = '';

    const password = passwordInput.value;
    const confirmPassword = confirmPasswordInput.value;
    let valid = true;

    const regex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;

    if (password.length < 8) {
      passwordError.textContent = 'Password must be at least 8 characters.';
      valid = false;
    } else if (!regex.test(password)) {
      passwordError.textContent = 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.';
      valid = false;
    }

    if (confirmPassword !== password) {
      confirmPasswordError.textContent = 'Passwords do not match.';
      valid = false;
    }
    
  });
</script>


