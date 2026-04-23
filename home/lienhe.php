<?php
require_once dirname(__DIR__) . '/config.php';
session_start();

if (isset($_GET['login']) && $_GET['login'] === 'dangxuat') {
    session_destroy();
    header('Location: lienhe.php');
    exit;
}

include BASE_PATH . 'includes/header.php';

// UC4: Gửi thông điệp/Feedback
$mailSent = false;
$mailError = false;

function GuiMail() {
    require BASE_PATH . 'PHPMailer-master/src/PHPMailer.php';
    require BASE_PATH . 'PHPMailer-master/src/SMTP.php';
    require BASE_PATH . 'PHPMailer-master/src/Exception.php';
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->SMTPDebug = 0;
        $mail->isSMTP();
        $mail->CharSet = "utf-8";
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'remkyorosi@gmail.com';
        $mail->Password = 'nvui gcgt snxd rpib';
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;
        $mail->setFrom('remkyorosi@gmail.com', 'Shop Sneakers');
        $mail->addAddress('remkyorosi@gmail.com', 'Nguyễn Văn Kiên');
        $mail->isHTML(true);
        $mail->Subject = 'Liên hệ từ khách hàng';
        $noidungthu = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <h2 style='color: #111; border-bottom: 2px solid #111; padding-bottom: 10px;'>📩 Thư liên hệ từ khách hàng</h2>
                <table style='width: 100%; border-collapse: collapse;'>
                    <tr><td style='padding: 12px; border-bottom: 1px solid #eee; color: #888; width: 120px;'>Họ tên:</td><td style='padding: 12px; border-bottom: 1px solid #eee; font-weight: 600;'>" . htmlspecialchars($_POST['name']) . "</td></tr>
                    <tr><td style='padding: 12px; border-bottom: 1px solid #eee; color: #888;'>Email:</td><td style='padding: 12px; border-bottom: 1px solid #eee; font-weight: 600;'>" . htmlspecialchars($_POST['email']) . "</td></tr>
                    <tr><td style='padding: 12px; color: #888; vertical-align: top;'>Nội dung:</td><td style='padding: 12px; line-height: 1.6;'>" . nl2br(htmlspecialchars($_POST['message'])) . "</td></tr>
                </table>
                <p style='color: #aaa; font-size: 12px; margin-top: 20px;'>Gửi lúc: " . date('d/m/Y H:i:s') . "</p>
            </div>";
        $mail->Body = $noidungthu;
        $mail->smtpConnect(array("ssl" => array("verify_peer" => false, "verify_peer_name" => false,
            "allow_self_signed" => true)));
        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

if (isset($_POST['btn'])) {
    // Server-side validation
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    if (!empty($name) && !empty($email) && !empty($message) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mailSent = GuiMail();
    } else {
        $mailError = true;
    }
}
?>

<style>
    /* ===== Contact Page (MOBILE-FIRST) ===== */
    .contact-page {
        padding: clamp(90px, 14vw, 130px) 0 0;
        background: #f1f5f9;
    }

    /* Hero */
    .contact-hero {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
        padding: clamp(40px, 8vw, 60px) 0;
        text-align: center;
        color: #fff;
        margin-bottom: clamp(30px, 6vw, 60px);
        position: relative;
        overflow: hidden;
    }
    .contact-hero::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle at 30% 50%, rgba(59, 130, 246, 0.08) 0%, transparent 50%),
                    radial-gradient(circle at 70% 80%, rgba(139, 92, 246, 0.06) 0%, transparent 50%);
        animation: heroFloat 20s ease-in-out infinite;
    }
    @keyframes heroFloat {
        0%, 100% { transform: translate(0, 0); }
        50% { transform: translate(-20px, -10px); }
    }
    .contact-hero h2 {
        font-family: 'Montserrat', sans-serif;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: clamp(1px, 0.5vw, 3px);
        font-size: clamp(1.5rem, 5vw, 2.8rem);
        margin-bottom: 10px;
        position: relative;
        z-index: 1;
    }
    .contact-hero p {
        color: #94a3b8;
        font-size: clamp(0.88rem, 2vw, 1.05rem);
        position: relative;
        z-index: 1;
        padding: 0 16px;
    }

    /* Info Cards — mobile-first grid */
    .info-cards {
        display: grid;
        grid-template-columns: 1fr;
        gap: clamp(12px, 3vw, 20px);
        margin-bottom: clamp(30px, 6vw, 50px);
    }
    @media (min-width: 576px) {
        .info-cards { grid-template-columns: 1fr 1fr; }
    }
    @media (min-width: 992px) {
        .info-cards { grid-template-columns: repeat(4, 1fr); }
    }
    .info-card {
        background: #fff;
        border-radius: 16px;
        padding: clamp(20px, 4vw, 30px);
        text-align: center;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        border: 1px solid #e2e8f0;
        transition: all 0.3s ease;
    }
    @media (hover: hover) {
        .info-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.1);
            border-color: #cbd5e1;
        }
    }
    .info-card-icon {
        width: clamp(50px, 10vw, 60px);
        height: clamp(50px, 10vw, 60px);
        border-radius: 50%;
        background: #1e293b;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto clamp(12px, 3vw, 18px);
        font-size: clamp(1.1rem, 2.5vw, 1.3rem);
    }
    .info-card h5 {
        font-weight: 800;
        color: #1e293b;
        margin-bottom: 8px;
        font-size: clamp(0.85rem, 2vw, 1rem);
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    .info-card p {
        color: #64748b;
        font-size: clamp(0.82rem, 1.8vw, 0.92rem);
        line-height: 1.6;
        margin: 0;
        word-break: break-word;
    }
    .info-card a {
        color: #1e293b;
        text-decoration: none;
        font-weight: 600;
        min-height: 44px;
        display: inline-flex;
        align-items: center;
    }
    @media (hover: hover) {
        .info-card a:hover { color: #3b82f6; }
    }

    /* Main Content — mobile-first grid */
    .contact-main {
        display: grid;
        grid-template-columns: 1fr;
        gap: clamp(20px, 4vw, 40px);
        margin-bottom: clamp(30px, 6vw, 60px);
    }
    @media (min-width: 768px) {
        .contact-main { grid-template-columns: 1fr 1fr; }
    }

    /* Form */
    .form-wrapper {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        padding: clamp(20px, 4vw, 40px);
        border: 1px solid #e2e8f0;
    }
    .form-wrapper h3 {
        font-family: 'Montserrat', sans-serif;
        font-weight: 900;
        color: #1e293b;
        margin-bottom: 6px;
        font-size: clamp(1.1rem, 3vw, 1.4rem);
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    .form-wrapper .form-subtitle {
        color: #94a3b8;
        margin-bottom: clamp(20px, 4vw, 30px);
        font-size: clamp(0.82rem, 1.8vw, 0.9rem);
    }
    .contact-page .form-control {
        border-radius: 10px;
        border: 2px solid #e2e8f0;
        padding: 12px 16px;
        box-shadow: none;
        transition: border-color 0.3s, box-shadow 0.3s;
        font-size: clamp(0.88rem, 2vw, 0.95rem);
        min-height: 48px;
    }
    .contact-page .form-control:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    .contact-page .form-label {
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 8px;
        font-size: clamp(0.82rem, 1.8vw, 0.88rem);
    }
    .btn-editorial-solid {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: #fff;
        border: none;
        border-radius: 10px;
        padding: clamp(12px, 3vw, 14px) clamp(24px, 5vw, 40px);
        font-weight: 800;
        font-size: clamp(0.85rem, 2vw, 0.95rem);
        text-transform: uppercase;
        transition: all 0.3s ease;
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 100%;
        letter-spacing: 1px;
        cursor: pointer;
        min-height: 48px;
    }
    @media (hover: hover) {
        .btn-editorial-solid:hover {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.3);
        }
    }

    /* Map */
    .map-wrapper {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        overflow: hidden;
        border: 1px solid #e2e8f0;
        display: flex;
        flex-direction: column;
    }
    .map-header {
        padding: clamp(16px, 3vw, 24px) clamp(20px, 4vw, 30px);
        border-bottom: 1px solid #e2e8f0;
    }
    .map-header h3 {
        font-family: 'Montserrat', sans-serif;
        font-weight: 900;
        color: #1e293b;
        margin-bottom: 4px;
        font-size: clamp(1rem, 2.5vw, 1.2rem);
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    .map-header p {
        color: #94a3b8;
        font-size: clamp(0.78rem, 1.8vw, 0.88rem);
        margin: 0;
    }
    .map-iframe {
        flex: 1;
        min-height: clamp(250px, 40vw, 380px);
    }
    .map-iframe iframe {
        width: 100%;
        height: 100%;
        border: none;
        display: block;
    }

    /* Alerts */
    .alert-custom {
        padding: clamp(12px, 3vw, 16px) clamp(16px, 3vw, 24px);
        border-radius: 12px;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 12px;
        font-weight: 600;
        font-size: clamp(0.82rem, 2vw, 0.92rem);
    }
    .alert-success-custom { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
    .alert-error-custom { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
    .alert-custom i { font-size: clamp(1.1rem, 2.5vw, 1.3rem); flex-shrink: 0; }
</style>

<main class="page contact-page">
    <!-- Hero -->
    <section class="contact-hero">
        <div class="container">
            <h2>LIÊN HỆ CHÚNG TÔI</h2>
            <p>Tận tình – Chuyên nghiệp – Tiêu chuẩn phục vụ cao cấp nhất.</p>
        </div>
    </section>

    <div class="container">
        <!-- UC2: Thông tin liên hệ Shop -->
        <div class="info-cards">
            <div class="info-card">
                <div class="info-card-icon">
                    <i class="fas fa-map-marker-alt"></i>
                </div>
                <h5>Địa chỉ</h5>
                <p>Số 6 đường Thắng Lợi 1,<br>Hồng Hà, Hà Nội</p>
            </div>
            <div class="info-card">
                <div class="info-card-icon">
                    <i class="fas fa-phone-alt"></i>
                </div>
                <h5>Điện thoại</h5>
                <p><a href="tel:0394680113">0394 680 113</a></p>
                <p style="font-size: 0.82rem; color: #aaa; margin-top: 4px;">Hỗ trợ 8:00 - 22:00</p>
            </div>
            <div class="info-card">
                <div class="info-card-icon">
                    <i class="fas fa-envelope"></i>
                </div>
                <h5>Email</h5>
                <p><a href="mailto:remkyorosi@gmail.com">remkyorosi@gmail.com</a></p>
                <p style="font-size: 0.82rem; color: #aaa; margin-top: 4px;">Phản hồi trong 24h</p>
            </div>
            <div class="info-card">
                <div class="info-card-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <h5>Giờ mở cửa</h5>
                <p>Thứ 2 - Thứ 7: 8:00 - 22:00<br>Chủ nhật: 9:00 - 21:00</p>
            </div>
        </div>

        <!-- UC3 + UC4: Form + Map -->
        <div class="contact-main">
            <!-- Form liên hệ -->
            <div class="form-wrapper">
                <h3><i class="fas fa-paper-plane" style="margin-right: 8px;"></i> Gửi lời nhắn</h3>
                <p class="form-subtitle">Hãy để lại thông tin, chúng tôi sẽ phản hồi sớm nhất có thể.</p>

                <?php if ($mailSent): ?>
                <div class="alert-custom alert-success-custom">
                    <i class="fas fa-check-circle"></i>
                    <span>Thư của bạn đã được gửi thành công! Chúng tôi sẽ phản hồi sớm.</span>
                </div>
                <?php elseif ($mailError): ?>
                <div class="alert-custom alert-error-custom">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>Vui lòng điền đầy đủ thông tin hợp lệ.</span>
                </div>
                <?php endif; ?>

                <form method="post" id="contactForm" novalidate>
                    <div class="mb-4">
                        <label class="form-label" for="contact-name">Họ và tên <span style="color: #e63946;">*</span></label>
                        <input class="form-control" type="text" id="contact-name" name="name" placeholder="Ví dụ: Nguyễn Văn A" required>
                        <div class="invalid-feedback" style="display:none; color: #e63946; font-size: 0.82rem; margin-top: 4px; font-weight: 600;">Vui lòng nhập họ tên</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label" for="contact-email">Email liên hệ <span style="color: #e63946;">*</span></label>
                        <input class="form-control" type="email" id="contact-email" name="email" placeholder="example@gmail.com" required>
                        <div class="invalid-feedback" style="display:none; color: #e63946; font-size: 0.82rem; margin-top: 4px; font-weight: 600;">Vui lòng nhập email hợp lệ</div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label" for="contact-message">Nội dung <span style="color: #e63946;">*</span></label>
                        <textarea class="form-control" id="contact-message" name="message" rows="5" placeholder="Để lại lời nhắn của bạn tại đây..." required></textarea>
                        <div class="invalid-feedback" style="display:none; color: #e63946; font-size: 0.82rem; margin-top: 4px; font-weight: 600;">Vui lòng nhập nội dung</div>
                    </div>
                    
                    <div class="mt-5">
                        <button class="btn-editorial-solid" name="btn" type="submit">
                            <i class="fas fa-paper-plane"></i> GỬI YÊU CẦU
                        </button>
                    </div>
                </form>
            </div>

            <!-- Google Maps -->
            <div class="map-wrapper">
                <div class="map-header">
                    <h3><i class="fas fa-map-marked-alt" style="margin-right: 8px;"></i> Vị trí cửa hàng</h3>
                    <p>Số 6 đường Thắng Lợi 1, Hồng Hà, Hà Nội</p>
                </div>
                <div class="map-iframe">
                    <iframe 
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3723.6577!2d105.8342!3d21.0285!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMjHCsDAxJzQyLjYiTiAxMDXCsDUwJzAzLjEiRQ!5e0!3m2!1svi!2svn!4v1690000000000!5m2!1svi!2svn"
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                        allowfullscreen>
                    </iframe>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Client-side Validation -->
<script>
(function() {
    const form = document.getElementById('contactForm');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        let valid = true;
        const fields = [
            { el: document.getElementById('contact-name'), type: 'text' },
            { el: document.getElementById('contact-email'), type: 'email' },
            { el: document.getElementById('contact-message'), type: 'text' },
        ];

        fields.forEach(function(field) {
            const feedback = field.el.parentElement.querySelector('.invalid-feedback');
            const val = field.el.value.trim();
            
            if (!val) {
                field.el.style.borderColor = '#e63946';
                if (feedback) feedback.style.display = 'block';
                valid = false;
            } else if (field.type === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) {
                field.el.style.borderColor = '#e63946';
                if (feedback) {
                    feedback.textContent = 'Email không đúng định dạng';
                    feedback.style.display = 'block';
                }
                valid = false;
            } else {
                field.el.style.borderColor = '#e0e0e0';
                if (feedback) feedback.style.display = 'none';
            }
        });

        if (!valid) {
            e.preventDefault();
        }
    });

    // Clear error on input
    document.querySelectorAll('#contactForm .form-control').forEach(function(input) {
        input.addEventListener('input', function() {
            this.style.borderColor = '#e0e0e0';
            const feedback = this.parentElement.querySelector('.invalid-feedback');
            if (feedback) feedback.style.display = 'none';
        });
    });
})();
</script>

<?php include BASE_PATH . 'includes/footer.php'; ?>