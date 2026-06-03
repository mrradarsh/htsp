<?php
// Define security constant for log inclusion
define('SECURE_LOG', true);

// Configure Email recipients
$to_emails = 'official.holyshift@gmail.com, devilonearth789@gmail.com';

// Handle Form Submission (POST)
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Retrieve and Sanitize inputs
    $name = isset($_POST['name']) ? strip_tags(trim($_POST['name'])) : '';
    $email = isset($_POST['email']) ? filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL) : '';
    $phone = isset($_POST['phone']) ? strip_tags(trim($_POST['phone'])) : '';
    $category = isset($_POST['category']) ? strip_tags(trim($_POST['category'])) : '';
    $preference = isset($_POST['preference']) ? strip_tags(trim($_POST['preference'])) : 'WhatsApp';
    $message = isset($_POST['message']) ? strip_tags(trim($_POST['message'])) : '';
    
    // 2. Validate inputs
    if (empty($name)) {
        $errors['name'] = 'कृपया आपले नाव प्रविष्ट करा (Please enter your name).';
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'कृपया एक वैध ईमेल प्रविष्ट करा (Please enter a valid email).';
    }
    
    if (empty($phone)) {
        $errors['phone'] = 'कृपया आपला मोबाईल नंबर प्रविष्ट करा (Please enter your phone number).';
    } elseif (!preg_match('/^[0-9\-\+\s]{8,15}$/', $phone)) {
        $errors['phone'] = 'कृपया एक वैध मोबाईल नंबर प्रविष्ट करा (Please enter a valid phone number).';
    }
    
    if (empty($category)) {
        $errors['category'] = 'कृपया एक पर्याय निवडा (Please select a category).';
    }
    
    if (empty($message)) {
        $errors['message'] = 'कृपया आपला संदेश प्रविष्ट करा (Please enter your message).';
    }
    
    // 3. Process if no errors
    if (empty($errors)) {
        // A. Secure Local Log Storage
        $log_file = __DIR__ . '/submissions_log.php';
        if (file_exists($log_file)) {
            $log_content = file_get_contents($log_file);
            // Remove PHP wrapper code to extract JSON array
            $json_str = str_replace(['<?php/*', '*/?>', '<?php /*', '*/ ?>'], '', $log_content);
            $json_str = trim($json_str);
            $submissions = json_decode($json_str, true);
            if (!is_array($submissions)) {
                $submissions = [];
            }
        } else {
            $submissions = [];
        }
        
        $new_submission = [
            'id' => uniqid(),
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'category' => $category,
            'preference' => $preference,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
        ];
        
        array_unshift($submissions, $new_submission); // Newest first
        
        // Re-write securely with PHP execution protection block
        $secure_data = "<?php /*\n" . json_encode($submissions, JSON_PRETTY_PRINT) . "\n*/ ?>";
        file_put_contents($log_file, $secure_data);
        
        // B. Send Email Notification
        $subject = "[New Contact Inquiry] " . $category . " - " . $name;
        
        $email_body = "
        <html>
        <head>
            <title>New Contact Form Inquiry</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; border: 1px solid #ddd; padding: 20px; border-radius: 8px; }
                .header { background: #03170c; color: #F4AE00; padding: 15px; text-align: center; border-radius: 8px 8px 0 0; }
                .field { margin-bottom: 12px; }
                .label { font-weight: bold; color: #03170c; }
                .value { margin-top: 4px; padding: 8px; background: #f9f9f9; border-radius: 4px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>The H.O.L.Y. Shift Method™</h2>
                    <p>New Contact Inquiry Received</p>
                </div>
                <div style='padding: 20px 0;'>
                    <div class='field'>
                        <div class='label'>Name:</div>
                        <div class='value'>" . htmlspecialchars($name) . "</div>
                    </div>
                    <div class='field'>
                        <div class='label'>Email:</div>
                        <div class='value'><a href='mailto:" . htmlspecialchars($email) . "'>" . htmlspecialchars($email) . "</a></div>
                    </div>
                    <div class='field'>
                        <div class='label'>Phone / WhatsApp:</div>
                        <div class='value'>" . htmlspecialchars($phone) . "</div>
                    </div>
                    <div class='field'>
                        <div class='label'>Category of Help:</div>
                        <div class='value'>" . htmlspecialchars($category) . "</div>
                    </div>
                    <div class='field'>
                        <div class='label'>Preferred Contact Method:</div>
                        <div class='value'>" . htmlspecialchars($preference) . "</div>
                    </div>
                    <div class='field'>
                        <div class='label'>Message:</div>
                        <div class='value' style='white-space: pre-line;'>" . nl2br(htmlspecialchars($message)) . "</div>
                    </div>
                </div>
            </div>
        </body>
        </html>
        ";
        
        // Email headers
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: noreply@holyshiftmethod.com" . "\r\n"; // Ensure domain matches for deliverability
        $headers .= "Reply-To: " . $email . "\r\n";
        
        // Note: mail() might fail on local servers or servers with disabled mail services.
        // We suppress errors using @ and check, but submissions are already logged in submissions_log.php
        @mail($to_emails, $subject, $email_body, $headers);
        
        // C. Send Auto-Reply / Thank You Email to User
        $reply_subject = "Thank You for Contacting The H.O.L.Y. Shift Method™ 💛";
        $reply_body = "
        <html>
        <head>
            <title>Thank You for Contacting Us</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; border: 1px solid #ddd; padding: 20px; border-radius: 8px; }
                .header { background: #03170c; color: #F4AE00; padding: 15px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { padding: 20px 0; }
                .footer { text-align: center; font-size: 0.8rem; color: #777; border-top: 1px solid #eee; padding-top: 15px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>The H.O.L.Y. Shift Method™</h2>
                </div>
                <div class='content'>
                    <p>प्रिय <strong>" . htmlspecialchars($name) . "</strong>,</p>
                    <p>तुमचा संदेश आम्हाला यशस्वीरित्या प्राप्त झाला आहे. आमच्याशी संपर्क साधल्याबद्दल मनापासून धन्यवाद!</p>
                    <p>(We have successfully received your message. Thank you so much for reaching out to us!)</p>
                    
                    <p>आमची टीम तुमच्याशी लवकरच तुमच्या पसंतीच्या संपर्काचे माध्यम (<strong>" . htmlspecialchars($preference) . "</strong>) वर संपर्क साधेल.</p>
                    <p>(Our team will get in touch with you shortly on your preferred contact method: <strong>" . htmlspecialchars($preference) . "</strong>.)</p>
                    
                    <p>तुमचा पाठवलेला संदेश खालीलप्रमाणे आहे:<br>
                    (Here is a copy of your message:)</p>
                    <blockquote style='background: #f9f9f9; padding: 12px; border-left: 4px solid #F4AE00; margin: 15px 0;'>
                        " . nl2br(htmlspecialchars($message)) . "
                    </blockquote>
                    
                    <p>काळजी घ्या आणि सुखी राहा,<br>
                    The H.O.L.Y. Shift Method™ Team</p>
                </div>
                <div class='footer'>
                    <p>© 2026 The H.O.L.Y. Shift Method™ | Manisha Satpute</p>
                    <p>Questions? WhatsApp: +91 8855977468 | Email: official.holyshift@gmail.com</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $reply_headers = "MIME-Version: 1.0" . "\r\n";
        $reply_headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $reply_headers .= "From: official.holyshift@gmail.com" . "\r\n";
        $reply_headers .= "Reply-To: official.holyshift@gmail.com" . "\r\n";
        
        @mail($email, $reply_subject, $reply_body, $reply_headers);
        
        $success = true;
    }
    
    // 4. Return AJAX response if requested
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        if ($success) {
            echo json_encode(['success' => true, 'message' => 'धन्यवाद! तुमचा संदेश प्राप्त झाला आहे. आम्ही लवकरच संपर्क करू (Thank you! Your message has been received. We will contact you soon).']);
        } else {
            echo json_encode(['success' => false, 'errors' => $errors]);
        }
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="mr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0" />
  <title>Contact Us | The H.O.L.Y. Shift Method™</title>
  <meta name="description" content="Manisha Satpute यांच्याशी संपर्क साधा — emotional healing, workshop inquiries, आणि guidelines." />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800;900&family=Poppins:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet" />
  <style>
    /* ===== RESET & BASE ===== */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html { scroll-behavior: smooth; }
    body { background: #03170c; color: #fff; font-family: 'Inter', sans-serif; overflow-x: hidden; line-height: 1.6; min-height: 100vh; display: flex; flex-direction: column; }
    img { max-width: 100%; height: auto; display: block; }
    a { text-decoration: none; }

    /* ===== DESIGN TOKENS ===== */
    :root {
      --gold: #F4AE00;
      --gold-hover: #e09c00;
      --gold-light: rgba(244,174,0,0.12);
      --gold-border: rgba(244,174,0,0.25);
      --bg-deep: #03170c;
      --bg-card: rgba(255,255,255,0.04);
      --bg-card-hover: rgba(255,255,255,0.08);
      --text-main: #ffffff;
      --text-muted: rgba(255,255,255,0.75);
      --text-dim: rgba(255,255,255,0.45);
      --radius: 18px;
      --radius-sm: 10px;
    }

    /* ===== HEADER ===== */
    #site-header {
      background: rgba(3,23,12,0.96);
      border-bottom: 1px solid var(--gold-border);
      padding: 14px 5%;
      display: flex;
      justify-content: space-between;
      align-items: center;
      position: sticky;
      top: 0;
      z-index: 1000;
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
    }
    .header-brand { display: flex; align-items: center; gap: 12px; }
    .header-brand img { height: 48px; width: auto; }
    .header-brand-text { display: flex; flex-direction: column; }
    .header-brand-text .brand-name { font-family: 'Outfit', sans-serif; font-size: 0.95rem; font-weight: 800; color: var(--gold); letter-spacing: 0.5px; line-height: 1.2; }
    .header-brand-text .brand-sub { font-family: 'Inter', sans-serif; font-size: 0.65rem; font-weight: 600; color: rgba(255,255,255,0.55); letter-spacing: 2px; text-transform: uppercase; }
    
    .btn-primary {
      display: inline-block;
      background: linear-gradient(135deg, #F4AE00, #e09c00);
      color: #03170c;
      font-family: 'Poppins', sans-serif;
      font-size: 0.9rem;
      font-weight: 800;
      padding: 12px 30px;
      border-radius: 50px;
      letter-spacing: 0.3px;
      box-shadow: 0 6px 20px rgba(244,174,0,0.3);
      transition: transform 0.25s ease, box-shadow 0.25s ease;
      border: none; cursor: pointer;
    }
    .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(244,174,0,0.45); }

    /* ===== CONTENT AREA ===== */
    .content-wrapper {
      flex: 1;
      padding: 60px 5% 80px;
      position: relative;
      overflow: hidden;
      display: flex;
      justify-content: center;
      align-items: center;
    }
    .content-wrapper::before {
      content: '';
      position: absolute;
      top: 10%;
      left: 50%;
      transform: translateX(-50%);
      width: 600px;
      height: 600px;
      background: radial-gradient(circle, rgba(244,174,0,0.06) 0%, transparent 70%);
      pointer-events: none;
    }

    /* ===== GLASS CONTACT CARD ===== */
    .contact-card {
      background: var(--bg-card);
      border: 1px solid var(--gold-border);
      border-radius: var(--radius);
      padding: 40px;
      width: 100%;
      max-width: 620px;
      box-shadow: 0 20px 50px rgba(0,0,0,0.3);
      backdrop-filter: blur(8px);
      position: relative;
      z-index: 2;
    }
    .contact-card h1 {
      font-family: 'Outfit', sans-serif;
      font-size: 2.2rem;
      font-weight: 900;
      color: #fff;
      text-align: center;
      margin-bottom: 8px;
    }
    .contact-card h1 em {
      font-style: normal;
      color: var(--gold);
    }
    .contact-sub {
      text-align: center;
      font-size: 0.95rem;
      color: var(--text-muted);
      margin-bottom: 30px;
      line-height: 1.5;
    }

    /* ===== FORM FIELDS & FLOATING LABELS ===== */
    .form-group {
      position: relative;
      margin-bottom: 24px;
    }
    .form-group input, 
    .form-group textarea {
      width: 100%;
      background: rgba(255, 255, 255, 0.02);
      border: 1px solid var(--gold-border);
      border-radius: var(--radius-sm);
      color: #fff;
      padding: 20px 16px 8px;
      font-family: 'Poppins', sans-serif;
      font-size: 0.95rem;
      outline: none;
      transition: border-color 0.3s, box-shadow 0.3s;
    }
    .form-group input:focus, 
    .form-group textarea:focus {
      border-color: var(--gold);
      box-shadow: 0 0 10px rgba(244,174,0,0.2);
    }
    .form-group label {
      position: absolute;
      left: 16px;
      top: 16px;
      color: var(--text-dim);
      font-family: 'Inter', sans-serif;
      font-size: 0.95rem;
      pointer-events: none;
      transition: all 0.25s ease;
    }
    /* Floating Effect */
    .form-group input:focus ~ label,
    .form-group input:not(:placeholder-shown) ~ label,
    .form-group textarea:focus ~ label,
    .form-group textarea:not(:placeholder-shown) ~ label {
      top: 6px;
      left: 16px;
      font-size: 0.72rem;
      color: var(--gold);
      font-weight: 600;
    }
    .form-error {
      color: #ff6b6b;
      font-size: 0.8rem;
      margin-top: 4px;
      font-family: 'Inter', sans-serif;
      display: flex;
      align-items: center;
      gap: 4px;
    }
    .form-error::before {
      content: '⚠';
    }

    /* ===== HELP CATEGORY CHIPS ===== */
    .category-label {
      font-family: 'Poppins', sans-serif;
      font-size: 0.85rem;
      font-weight: 700;
      color: var(--gold);
      text-transform: uppercase;
      letter-spacing: 1px;
      margin-bottom: 12px;
      display: block;
    }
    .chips-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
      gap: 10px;
      margin-bottom: 24px;
    }
    .chip-item {
      position: relative;
    }
    .chip-item input[type="radio"] {
      position: absolute;
      opacity: 0;
      width: 0; height: 0;
    }
    .chip-label {
      display: block;
      background: rgba(255,255,255,0.03);
      border: 1px solid var(--gold-border);
      border-radius: 30px;
      padding: 10px 14px;
      font-family: 'Poppins', sans-serif;
      font-size: 0.8rem;
      font-weight: 600;
      color: var(--text-muted);
      text-align: center;
      cursor: pointer;
      transition: all 0.3s;
      white-space: nowrap;
    }
    .chip-item input[type="radio"]:checked + .chip-label {
      background: var(--gold-light);
      border-color: var(--gold);
      color: var(--gold);
      box-shadow: 0 0 12px rgba(244,174,0,0.2);
    }
    .chip-label:hover {
      background: var(--bg-card-hover);
      border-color: rgba(244,174,0,0.5);
    }

    /* ===== PREFERENCE SWITCHER (CARDS) ===== */
    .pref-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 12px;
      margin-bottom: 24px;
    }
    .pref-item {
      position: relative;
    }
    .pref-item input[type="radio"] {
      position: absolute;
      opacity: 0;
      width: 0; height: 0;
    }
    .pref-card {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 6px;
      background: rgba(255,255,255,0.02);
      border: 1px solid var(--gold-border);
      border-radius: 12px;
      padding: 14px 10px;
      cursor: pointer;
      transition: all 0.3s;
    }
    .pref-icon {
      font-size: 1.4rem;
    }
    .pref-text {
      font-family: 'Poppins', sans-serif;
      font-size: 0.8rem;
      font-weight: 600;
      color: var(--text-muted);
    }
    .pref-item input[type="radio"]:checked + .pref-card {
      background: var(--gold-light);
      border-color: var(--gold);
      box-shadow: 0 0 12px rgba(244,174,0,0.18);
    }
    .pref-item input[type="radio"]:checked + .pref-card .pref-text {
      color: var(--gold);
    }
    .pref-card:hover {
      background: var(--bg-card-hover);
      border-color: rgba(244,174,0,0.5);
    }

    /* ===== SUBMIT BUTTON & LOADER ===== */
    .btn-submit {
      width: 100%;
      padding: 16px;
      font-size: 1rem;
      font-weight: 700;
      border-radius: var(--radius-sm);
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 10px;
    }
    .spinner {
      width: 20px;
      height: 20px;
      border: 3px solid rgba(3,23,12,0.3);
      border-radius: 50%;
      border-top-color: #03170c;
      animation: spin 0.8s linear infinite;
      display: none;
    }
    @keyframes spin {
      to { transform: rotate(360deg); }
    }

    /* ===== SUCCESS VIEW ===== */
    .success-view {
      text-align: center;
      padding: 20px 10px;
    }
    .success-icon {
      width: 72px; height: 72px;
      background: linear-gradient(135deg, #F4AE00, #e09c00);
      border-radius: 50%;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 2rem;
      color: #03170c;
      margin-bottom: 24px;
      box-shadow: 0 0 30px rgba(244,174,0,0.3);
      animation: pop 0.5s ease;
    }
    @keyframes pop {
      0% { transform: scale(0); opacity: 0; }
      80% { transform: scale(1.1); }
      100% { transform: scale(1); opacity: 1; }
    }
    .success-view h2 {
      font-family: 'Outfit', sans-serif;
      font-size: 1.8rem;
      margin-bottom: 12px;
    }
    .success-text {
      color: var(--text-muted);
      font-size: 0.98rem;
      line-height: 1.7;
      margin-bottom: 30px;
    }

    /* ===== FOOTER ===== */
    #footer {
      background: #020e06;
      border-top: 1px solid var(--gold-border);
      padding: 30px 5%;
      text-align: center;
      margin-top: auto;
    }
    #footer p {
      font-family: 'Inter', sans-serif;
      font-size: 0.8rem;
      color: rgba(255,255,255,0.3);
    }

    /* ===== RESPONSIVE ===== */
    @media (max-width: 500px) {
      .contact-card {
        padding: 30px 20px;
      }
      .contact-card h1 {
        font-size: 1.8rem;
      }
      .chips-grid {
        grid-template-columns: repeat(2, 1fr);
      }
    }
  </style>
</head>
<body>

  <!-- HEADER -->
  <header id="site-header">
    <a href="index.html" class="header-brand">
      <img src="logo1.png" alt="The H.O.L.Y. Shift Method™ Logo" />
      <div class="header-brand-text">
        <span class="brand-name">The H.O.L.Y. Shift Method™</span>
        <span class="brand-sub">Inner Peace &amp; Healing</span>
      </div>
    </a>
    <a href="index.html#register" class="btn-primary" style="padding: 10px 22px; font-size: 0.85rem;">माझी जागा बुक करा</a>
  </header>

  <!-- CONTENT WRAPPER -->
  <div class="content-wrapper">
    <div class="contact-card" id="contactContainer">
      
      <?php if ($success): ?>
        <!-- Success View (Direct POST submission success) -->
        <div class="success-view">
          <div class="success-icon">✓</div>
          <h2>संदेश यशस्वीरित्या पाठवला!</h2>
          <p class="success-text">
            धन्यवाद! तुमचा संदेश आम्हाला मिळाला आहे. Manisha Satpute यांची टीम लवकरच तुमच्याशी तुमच्या पसंतीच्या संपर्काचे माध्यम (<?php echo htmlspecialchars($preference); ?>) वर संपर्क साधेल.<br /><br />
            (Thank you! Your message was sent successfully. We will get in touch with you shortly on your preferred contact method: <?php echo htmlspecialchars($preference); ?>.)
          </p>
          <a href="index.html" class="btn-primary">मुख्य पानावर जा (Back to Home)</a>
        </div>
      <?php else: ?>
        <!-- Form View -->
        <h1>संपर्क <em>करा</em></h1>
        <p class="contact-sub">
          आपल्या शंका विचारा किंवा वैयक्तिक मार्गदर्शनासाठी संदेश पाठवा. आम्ही तुम्हाला मदत करण्यास उत्सुक आहोत.<br />
          (Ask queries or message for personal guidance. We'd love to help you.)
        </p>

        <form action="contact.php" method="POST" id="contactForm">
          <!-- Full Name -->
          <div class="form-group">
            <input type="text" name="name" id="name" placeholder=" " value="<?php echo htmlspecialchars($name ?? ''); ?>" required autocomplete="name" />
            <label for="name">पूर्ण नाव (Full Name)</label>
            <?php if (isset($errors['name'])): ?>
              <div class="form-error"><?php echo $errors['name']; ?></div>
            <?php endif; ?>
          </div>

          <!-- Email -->
          <div class="form-group">
            <input type="email" name="email" id="email" placeholder=" " value="<?php echo htmlspecialchars($email ?? ''); ?>" required autocomplete="email" />
            <label for="email">ईमेल (Email Address)</label>
            <?php if (isset($errors['email'])): ?>
              <div class="form-error"><?php echo $errors['email']; ?></div>
            <?php endif; ?>
          </div>

          <!-- Phone Number -->
          <div class="form-group">
            <input type="tel" name="phone" id="phone" placeholder=" " value="<?php echo htmlspecialchars($phone ?? ''); ?>" required autocomplete="tel" />
            <label for="phone">मोबाईल नंबर (WhatsApp/Phone Number)</label>
            <?php if (isset($errors['phone'])): ?>
              <div class="form-error"><?php echo $errors['phone']; ?></div>
            <?php endif; ?>
          </div>

          <!-- Help Category Selector -->
          <span class="category-label">मार्गदर्शन हवी असलेली श्रेणी (Help Category)</span>
          <div class="chips-grid">
            <?php 
            $categories = [
                '🌀 overthinking' => 'Overthinking',
                '💔 emotional' => 'Emotional Pain',
                '🔁 relationship' => 'Relationship',
                '💰 abundance' => 'Abundance',
                '✉ other' => 'Other Inquiry'
            ];
            $selected_cat = $category ?? '🌀 overthinking';
            foreach ($categories as $value => $label):
            ?>
              <div class="chip-item">
                <input type="radio" name="category" id="cat_<?php echo str_replace(' ', '_', $value); ?>" value="<?php echo htmlspecialchars($value); ?>" <?php echo ($selected_cat === $value) ? 'checked' : ''; ?> />
                <label for="cat_<?php echo str_replace(' ', '_', $value); ?>" class="chip-label"><?php echo $label; ?></label>
              </div>
            <?php endforeach; ?>
          </div>
          <?php if (isset($errors['category'])): ?>
            <div class="form-error" style="margin-top: -16px; margin-bottom: 20px;"><?php echo $errors['category']; ?></div>
          <?php endif; ?>

          <!-- Contact Preference Selector -->
          <span class="category-label">संपर्क करण्याचे पसंतीचे माध्यम (Preferred Contact Method)</span>
          <div class="pref-grid">
            <div class="pref-item">
              <input type="radio" name="preference" id="pref_wa" value="WhatsApp" checked />
              <label for="pref_wa" class="pref-card">
                <span class="pref-icon">💬</span>
                <span class="pref-text">WhatsApp</span>
              </label>
            </div>
            <div class="pref-item">
              <input type="radio" name="preference" id="pref_mail" value="Email" />
              <label for="pref_mail" class="pref-card">
                <span class="pref-icon">✉</span>
                <span class="pref-text">Email</span>
              </label>
            </div>
            <div class="pref-item">
              <input type="radio" name="preference" id="pref_call" value="Call" />
              <label for="pref_call" class="pref-card">
                <span class="pref-icon">📞</span>
                <span class="pref-text">Phone Call</span>
              </label>
            </div>
          </div>

          <!-- Message -->
          <div class="form-group">
            <textarea name="message" id="message" rows="4" placeholder=" " required><?php echo htmlspecialchars($message ?? ''); ?></textarea>
            <label for="message">तुमचा संदेश (Your Message)</label>
            <?php if (isset($errors['message'])): ?>
              <div class="form-error"><?php echo $errors['message']; ?></div>
            <?php endif; ?>
          </div>

          <!-- Submit Button -->
          <button type="submit" class="btn-primary btn-submit" id="submitBtn">
            <span>संदेश पाठवा (Send Message)</span>
            <div class="spinner" id="btnSpinner"></div>
          </button>
        </form>
      <?php endif; ?>
      
    </div>
  </div>

  <!-- FOOTER -->
  <footer id="footer">
    <p>© 2026 The H.O.L.Y. Shift Method™. All rights reserved. | Manisha Satpute</p>
  </footer>

  <!-- AJAX script for smooth form submission -->
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const form = document.getElementById('contactForm');
      const container = document.getElementById('contactContainer');
      const submitBtn = document.getElementById('submitBtn');
      const spinner = document.getElementById('btnSpinner');

      if (!form) return;

      form.addEventListener('submit', function(e) {
        // Only run AJAX if JavaScript is working fine
        e.preventDefault();

        // 1. Show loading state
        submitBtn.disabled = true;
        submitBtn.style.opacity = '0.8';
        spinner.style.display = 'block';

        // Remove old errors
        document.querySelectorAll('.form-error').forEach(el => el.remove());

        // 2. Prep form data
        const formData = new FormData(form);

        // 3. Post to contact.php
        fetch('contact.php', {
          method: 'POST',
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: formData
        })
        .then(response => {
          if (!response.ok) {
            throw new Error('Network response was not ok');
          }
          return response.json();
        })
        .then(data => {
          if (data.success) {
            // Get checked preference value
            const preference = form.querySelector('input[name="preference"]:checked')?.value || 'WhatsApp';
            
            // Render creative success view
            container.innerHTML = `
              <div class="success-view">
                <div class="success-icon">✓</div>
                <h2>संदेश यशस्वीरित्या पाठवला!</h2>
                <p class="success-text">
                  धन्यवाद! तुमचा संदेश आम्हाला मिळाला आहे. Manisha Satpute यांची टीम लवकरच तुमच्याशी तुमच्या पसंतीच्या संपर्काचे माध्यम (<strong>\${preference}</strong>) वर संपर्क साधेल.<br /><br />
                  (Thank you! Your message was sent successfully. We will get in touch with you shortly on your preferred contact method: <strong>\${preference}</strong>.)
                </p>
                <a href="index.html" class="btn-primary">मुख्य पानावर जा (Back to Home)</a>
              </div>
            `;
          } else {
            // Re-enable button
            submitBtn.disabled = false;
            submitBtn.style.opacity = '1';
            spinner.style.display = 'none';

            // Show validation errors
            for (const field in data.errors) {
              const inputEl = document.getElementById(field) || document.querySelector(`input[name="\${field}"]`);
              if (inputEl) {
                const group = inputEl.closest('.form-group') || inputEl.parentNode;
                const errorDiv = document.createElement('div');
                errorDiv.className = 'form-error';
                errorDiv.innerText = data.errors[field];
                group.appendChild(errorDiv);
              }
            }
          }
        })
        .catch(error => {
          console.error('Error submitting form:', error);
          // Fallback to standard form submission if AJAX fails
          form.submit();
        });
      });
    });
  </script>
</body>
</html>
