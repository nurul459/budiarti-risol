
<?php
session_start();
include "../config/koneksi.php";

$error = "";

if (isset($_POST['login'])) {

    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Menggunakan prepared statement agar lebih aman
    $stmt = mysqli_prepare(
        $koneksi,
        "SELECT * FROM users WHERE email = ? LIMIT 1"
    );

    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if ($result && mysqli_num_rows($result) === 1) {

        $user = mysqli_fetch_assoc($result);

        if (password_verify($password, $user['password'])) {

            session_regenerate_id(true);

            $_SESSION['id_user'] = $user['id_user'];
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];

            if ($_SESSION['role'] === 'admin') {
                header("Location: ../admin/dashboard.php");
                exit;
            } else {
                header("Location: index.php");
                exit;
            }

        } else {
            $error = "Email atau password yang Anda masukkan salah.";
        }

    } else {
        $error = "Email atau password yang Anda masukkan salah.";
    }

    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login | Budiarti Risol</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700;800&display=swap" rel="stylesheet">

    <style>

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    :root {
        --cream: #F8EFE5;
        --brown: #542D1C;
        --dark: #542D1C;
        --caramel: #C99B78;
        --gold: #d8ae77;
        --muted: #9c8878;
    }

    body {
        min-height: 100vh;
        font-family: 'DM Sans', sans-serif;

        display: flex;
        justify-content: center;
        align-items: center;

        padding: 35px 20px;

        background:
            linear-gradient(
                125deg,
                rgba(35, 17, 10, 0.91),
                rgba(83, 43, 23, 0.82),
                rgba(39, 21, 13, 0.94)
            ),
            url("../assets/login-bg.jpg");

        background-size: cover;
        background-position: center;
        background-attachment: fixed;

        position: relative;
        overflow-x: hidden;
    }

    /* Dekorasi background */

    body::before {
        content: "";
        position: fixed;

        width: 500px;
        height: 500px;

        top: -200px;
        right: -120px;

        border: 1px solid rgba(222, 180, 123, 0.18);
        border-radius: 50%;

        box-shadow:
            0 0 0 35px rgba(222, 180, 123, 0.025),
            0 0 0 75px rgba(222, 180, 123, 0.025);

        pointer-events: none;
    }

    body::after {
        content: "";
        position: fixed;

        width: 400px;
        height: 400px;

        bottom: -200px;
        left: -130px;

        border: 1px solid rgba(222, 180, 123, 0.15);
        border-radius: 50%;

        box-shadow:
            0 0 0 30px rgba(222, 180, 123, 0.025),
            0 0 0 65px rgba(222, 180, 123, 0.025);

        pointer-events: none;
    }

    /* Main Container */

    .login-wrapper {
        width: 100%;
        max-width: 1050px;

        min-height: 650px;

        display: grid;
        grid-template-columns: 0.95fr 1.05fr;

        background: var(--cream);

        border-radius: 25px;
        overflow: hidden;

        position: relative;
        z-index: 2;

        box-shadow:
            0 35px 100px rgba(0, 0, 0, 0.38),
            0 5px 20px rgba(0, 0, 0, 0.12);

        animation: appear 0.8s ease;
    }

    @keyframes appear {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* LEFT BRANDING */

    .brand-panel {
        position: relative;

        background:
            linear-gradient(
                rgba(49, 25, 14, 0.52),
                rgba(49, 25, 14, 0.86)
            ),
            url("../assets/login-bg.jpg");

        background-size: cover;
        background-position: center;

        padding: 55px 45px;

        display: flex;
        flex-direction: column;
        justify-content: space-between;

        color: white;

        overflow: hidden;
    }

    .brand-panel::before {
        content: "";

        position: absolute;
        inset: 18px;

        border: 1px solid rgba(224, 184, 132, 0.35);

        border-radius: 14px;

        pointer-events: none;
    }

    .brand-panel::after {
        content: "✦";

        position: absolute;

        right: 55px;
        top: 70px;

        font-size: 45px;

        color: rgba(231, 194, 145, 0.55);
    }

    .panel-content {
        position: relative;
        z-index: 1;

        margin-top: 85px;
    }

    .panel-label {
        color: #e6bc8b;

        font-size: 10px;
        font-weight: 700;

        letter-spacing: 4px;

        text-transform: uppercase;

        margin-bottom: 22px;
    }

    .panel-title {
        font-family: 'Playfair Display', serif;

        font-size: 43px;
        line-height: 1.25;

        font-weight: 600;

        color: #F8EFE5;

        margin-bottom: 22px;
    }

    .panel-title span {
        color: #e4b47d;
        font-style: italic;
    }

    .panel-description {
        color: rgba(255, 250, 242, 0.77);

        font-size: 13px;
        line-height: 2;

        max-width: 310px;
    }

    .panel-divider {
        width: 55px;
        height: 2px;

        background: #d6a66e;

        margin: 28px 0;
    }

    .panel-quote {
        color: #f0d6b6;

        font-family: 'Playfair Display', serif;

        font-size: 17px;
        font-style: italic;

        line-height: 1.7;
    }

    .panel-bottom {
        position: relative;
        z-index: 1;

        display: flex;
        justify-content: space-between;
        align-items: center;

        padding-top: 25px;

        border-top: 1px solid rgba(255,255,255,0.2);
    }

    .panel-bottom p {
        font-size: 9px;

        color: rgba(255,255,255,0.65);

        letter-spacing: 1.5px;
    }

    .panel-bottom span {
        color: #e6bc8b;
        font-size: 11px;
    }

    /* RIGHT LOGIN */

    .login-panel {
        background:
            radial-gradient(
                circle at 100% 0%,
                rgba(211, 164, 111, 0.12),
                transparent 35%
            ),
            #fffaf3;

        padding: 50px 65px;

        display: flex;
        flex-direction: column;
        justify-content: center;

        position: relative;
    }

    /* Logo */

    .logo-area {
        text-align: center;
        margin-bottom: 35px;
    }

    .logo-icon {
        width: 65px;
        height: 65px;

        margin: 0 auto 17px;

        display: flex;
        justify-content: center;
        align-items: center;

        border-radius: 50%;

        background: linear-gradient(
            145deg,
            #F8EFE5
        );

        border: 3px solid #ead5b9;

        box-shadow:
            0 5px 18px rgba(76, 39, 22, 0.2),
            0 0 0 5px rgba(184, 117, 63, 0.08);

        color: #e8bd83;

        font-size: 29px;
    }

    .brand-name {
        display: flex;
        justify-content: center;
        align-items: baseline;

        gap: 8px;

        color: #452719;

        font-family: 'Playfair Display', serif;

        font-size: 30px;
        font-weight: 700;

        letter-spacing: -0.8px;
    }

    .brand-name span {
        color: #452719;
    }

    .brand-tagline {
        color: #a58c78;

        font-size: 9px;
        font-weight: 600;

        letter-spacing: 3px;

        margin-top: 10px;
    }

    .gold-line {
        width: 48px;
        height: 2px;

        background: linear-gradient(
            90deg,
            transparent,
            #c38a54,
            transparent
        );

        margin: 20px auto 0;
    }

    /* Welcome */

    .welcome {
        margin-bottom: 25px;
    }

    .welcome-label {
        color: #b17a4b;

        font-size: 15px;
        font-weight: 700;

        letter-spacing: 3px;

        margin-bottom: 9px;
    }

    .welcome h2 {
        font-family: 'Playfair Display', serif;

        font-size: 31px;
        font-weight: 700;

        color: #392015;

        margin-bottom: 9px;
    }

    .welcome p {
        color: #a18c7e;

        font-size: 13px;
        line-height: 1.8;
    }

    /* Form */

    .form-group {
        margin-bottom: 19px;
        text-align: left;
    }

    .form-group label {
        display: block;

        color: #583a29;

        font-size: 15px;
        font-weight: 700;

        margin-bottom: 9px;
    }

    .input-wrapper {
        position: relative;
    }

    .input-icon {
        position: absolute;

        left: 16px;
        top: 50%;

        transform: translateY(-50%);

        color: #b18a6b;

        font-size: 18px;
    }

    .form-group input {
        width: 100%;
        height: 49px;

        padding: 0 48px;

        border: 1px solid #eadbcb;
        border-radius: 10px;

        outline: none;

        background: rgba(255,255,255,0.78);

        color: #492b1d;

        font-family: 'DM Sans', sans-serif;
        font-size: 15px;

        transition: 0.3s;
    }

    .form-group input::placeholder {
        color: #bca99a;
    }

    .form-group input:focus {
        border-color: #ae7044;

        background: white;

        box-shadow: 0 0 0 4px rgba(174, 112, 68, 0.09);
    }

    .show-password {
        position: absolute;

        right: 14px;
        top: 50%;

        transform: translateY(-50%);

        border: none;
        background: none;

        color: #a36a42;

        font-family: 'DM Sans', sans-serif;
        font-size: 10px;
        font-weight: 700;

        cursor: pointer;
    }

    /* Remember */

    .form-options {
        display: flex;
        justify-content: flex-end;

        align-items: center;

        margin: 2px 0 25px;
    }

    .forgot {
        color: #a45e34;

        font-size: 11px;
        font-weight: 700;

        text-decoration: none;

        transition: 0.2s;
    }

    .forgot:hover {
        color: #62351f;
        text-decoration: underline;
    }

    /* Button */

    .btn-login {
        width: 100%;
        height: 50px;

        border: none;
        border-radius: 10px;

        background: linear-gradient(
            120deg,
            #492415,
            #874b2a,
            #a96a3e
        );

        color: #fffaf3;

        font-family: 'DM Sans', sans-serif;
        font-size: 12px;
        font-weight: 700;

        letter-spacing: 1px;

        cursor: pointer;

        box-shadow:
            0 8px 20px rgba(103, 54, 29, 0.2);

        transition: all 0.3s;
    }

    .btn-login:hover {
        transform: translateY(-3px);

        box-shadow:
            0 12px 25px rgba(103, 54, 29, 0.3);

        background: linear-gradient(
            120deg,
            #62331d,
            #a35e34,
            #bd7b4a
        );
    }

    .btn-login:active {
        transform: translateY(0);
    }

    /* Error */

    .error {
        padding: 12px 14px;

        margin-bottom: 18px;

        border: 1px solid #edcaca;
        border-radius: 9px;

        background: #fff0ed;

        color: #a53d32;

        font-size: 11px;
        line-height: 1.6;

        text-align: left;
    }

    /* Register */

    .register-link {
        margin-top: 26px;

        text-align: center;

        color: #a18c7e;

        font-size: 11px;
    }

    .register-link a {
        color: #9b552e;

        font-weight: 700;

        text-decoration: none;
    }

    .register-link a:hover {
        text-decoration: underline;
    }

    .secure-note {
        display: flex;

        justify-content: center;
        align-items: center;

        gap: 7px;

        margin-top: 35px;

        color: #b3a193;

        font-size: 9px;

        letter-spacing: 0.5px;
    }

    .secure-note span {
        color: #9c7958;
        font-size: 12px;
    }

    /* Responsive */

    @media (max-width: 850px) {

        .login-wrapper {
            max-width: 480px;
            grid-template-columns: 1fr;
        }

        .brand-panel {
            display: none;
        }

        .login-panel {
            padding: 50px 55px;
        }

    }

    @media (max-width: 500px) {

        body {
            padding: 18px 12px;
        }

        .login-wrapper {
            border-radius: 19px;
        }

        .login-panel {
            padding: 38px 25px;
        }

        .brand-name {
            font-size: 27px;
        }

        .welcome h2 {
            font-size: 27px;
        }

        .logo-area {
            margin-bottom: 30px;
        }

    }

    </style>

<style id="budiarti-brand-palette">
:root{--br-cream:#F8EFE5;--br-brown:#542D1C;--br-soft:#EBD3C0;--br-accent:#C99B78;--br-white:#FFFFFF;--br-text-muted:#80634F;--br-border:#E5D3C3;}
body{background-color:var(--br-cream);color:var(--br-brown);}
.admin-wrapper .sidebar,.sidebar,.side{background-color:var(--br-brown);}
.admin-wrapper .top-header,.main-content>.top-header{background-color:var(--br-white);color:var(--br-brown);border-bottom:1px solid var(--br-border);}
.navbar,header.navbar,.site-header{background-color:var(--br-brown);}
.card,.stat-card,.content-card,.table-container,.form-card,.panel,.account-dropdown{border-color:var(--br-border);}
button.btn-primary,.btn-primary,.button-primary,.add-button,.btn-tambah{background-color:var(--br-brown);color:#fff;border-color:var(--br-brown);}
button.btn-secondary,.btn-secondary,.button-secondary{background-color:var(--br-accent);color:var(--br-brown);}
input,select,textarea{border-color:var(--br-border);}
input:focus,select:focus,textarea:focus{border-color:var(--br-accent);outline-color:var(--br-accent);}
</style>
</head>

<body>

    <div class="login-wrapper">

        <!-- LEFT BRANDING PANEL -->

        <div class="brand-panel">

            <div class="panel-content">

                <div class="panel-label">
                    HOMEMADE • FRESHLY MADE
                </div>

                <h1 class="panel-title">
                    A Little Taste<br>
                    of <span>Happiness.</span>
                </h1>

                <p class="panel-description">
                    Dibuat dengan bahan pilihan, diproses
                    dengan penuh perhatian, dan disajikan
                    dengan cita rasa rumahan yang istimewa.
                </p>

                <div class="panel-divider"></div>

                <p class="panel-quote">
                    "Setiap gigitan punya cerita,
                    setiap rasa dibuat sepenuh hati."
                </p>

            </div>

            <div class="panel-bottom">

                <p>
                    BUDIARTI RISOL
                </p>

                <span>EST. 2026</span>

            </div>

        </div>


        <!-- RIGHT LOGIN PANEL -->

        <div class="login-panel">

            <!-- LOGO DAN BRAND -->

            <div class="logo-area">

                <div class="logo-icon">
                    👩‍🍳
                </div>

                <h1 class="brand-name">
                    Budiarti <span>Risol</span>
                </h1>

                <div class="brand-tagline">
                    HOMEMADE • FRESHLY MADE
                </div>

                <div class="gold-line"></div>

            </div>


            <!-- WELCOME -->

            <div class="welcome">

                <div class="welcome-label">
                    WELCOME BACK
                </div>

                <h2>Selamat Datang!</h2>

                <p>
                    Silakan masuk ke akun Anda untuk menikmati
                    pengalaman berbelanja bersama kami.
                </p>

            </div>


            <!-- ERROR MESSAGE -->

            <?php if ($error != "") : ?>

                <div class="error">
                    ⚠ <?= htmlspecialchars($error); ?>
                </div>

            <?php endif; ?>


            <!-- LOGIN FORM -->

            <form method="POST">

                <div class="form-group">

                    <label for="email">
                        Alamat Email
                    </label>

                    <div class="input-wrapper">

                        <span class="input-icon">✉</span>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            placeholder="nama@email.com"
                            value="<?= htmlspecialchars($_POST['email'] ?? ''); ?>"
                            autocomplete="username"
                            required
                        >

                    </div>

                </div>


                <div class="form-group">

                    <label for="password">
                        Password
                    </label>

                    <div class="input-wrapper">

                        <span class="input-icon">♙</span>

                        <input
                            type="password"
                            name="password"
                            id="password"
                            placeholder="Masukkan password Anda"
                            autocomplete="current-password"
                            required
                        >

                        <button
                            type="button"
                            class="show-password"
                            onclick="togglePassword()"
                        >
                            Lihat
                        </button>

                    </div>

                </div>


                <div class="form-options">

                    <a href="lupa_password.php" class="forgot">
                        Lupa password?
                    </a>

                </div>


                <button
                    type="submit"
                    name="login"
                    class="btn-login"
                >
                    MASUK KE AKUN &nbsp; →
                </button>

            </form>


            <!-- REGISTER -->

            <div class="register-link">

                Belum memiliki akun?

                <a href="register.php">
                    Daftar sekarang
                </a>

            </div>


            <!-- SECURITY NOTE -->

            <div class="secure-note">
                <span>♧</span>
                YOUR ACCOUNT IS SAFE WITH US
            </div>

        </div>

    </div>


    <script>

    function togglePassword() {

        const password = document.getElementById("password");

        const button = document.querySelector(".show-password");

        if (password.type === "password") {

            password.type = "text";
            button.textContent = "Sembunyikan";

        } else {

            password.type = "password";
            button.textContent = "Lihat";

        }

    }

    </script>

</body>
</html>
