
<?php
session_start();
include "../config/koneksi.php";

$error = "";

if (isset($_POST['register'])) {

    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $no_hp = trim($_POST['no_hp']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Mengecek email yang sudah terdaftar
    $stmt = mysqli_prepare(
        $koneksi,
        "SELECT id_user FROM users WHERE email = ? LIMIT 1"
    );

    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    $email_terdaftar = mysqli_stmt_num_rows($stmt) > 0;

    mysqli_stmt_close($stmt);

    if ($email_terdaftar) {

        $error = "Email sudah terdaftar. Silakan gunakan email lain.";

    } else {

        // Menyimpan akun baru
        $stmt = mysqli_prepare(
            $koneksi,
            "INSERT INTO users (nama, email, no_hp, password, role)
             VALUES (?, ?, ?, ?, 'user')"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "ssss",
            $nama,
            $email,
            $no_hp,
            $password
        );

        if (mysqli_stmt_execute($stmt)) {

            mysqli_stmt_close($stmt);

            echo "<script>
                alert('Registrasi berhasil! Silakan login.');
                window.location='login.php';
            </script>";

            exit;

        } else {

            $error = "Registrasi gagal. Silakan coba kembali.";
            mysqli_stmt_close($stmt);

        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Daftar | Budiarti Risol</title>

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
    }

    /* BACKGROUND DECORATION */

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

    /* MAIN CONTAINER */

    .register-wrapper {
        width: 100%;
        max-width: 1050px;

        min-height: 700px;

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

    /* LEFT BRANDING PANEL */

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

    /* RIGHT REGISTER PANEL */

    .register-panel {
        background:
            radial-gradient(
                circle at 100% 0%,
                rgba(211, 164, 111, 0.12),
                transparent 35%
            ),
            #fffaf3;

        padding: 42px 65px;

        display: flex;
        flex-direction: column;
        justify-content: center;

        position: relative;
    }

    /* LOGO */

    .logo-area {
        text-align: center;
        margin-bottom: 27px;
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

    /* WELCOME */

    .welcome {
        margin-bottom: 22px;
        text-align: left;
    }

    .welcome-label {
        color: #b17a4b;

        font-size: 10px;
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

    /* FORM */

    .form-group {
        margin-bottom: 15px;
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

        font-size: 16px;

        pointer-events: none;
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

    /* PASSWORD */

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

    .show-password:hover {
        color: #62351f;
    }

    /* ERROR */

    .error {
        padding: 12px 14px;

        margin-bottom: 17px;

        border: 1px solid #edcaca;
        border-radius: 9px;

        background: #fff0ed;

        color: #a53d32;

        font-size: 11px;
        line-height: 1.6;

        text-align: left;
    }

    /* REGISTER BUTTON */

    .btn-register {
        width: 100%;
        height: 50px;

        margin-top: 5px;

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

    .btn-register:hover {
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

    .btn-register:active {
        transform: translateY(0);
    }

    /* LOGIN LINK */

    .login-link {
        margin-top: 25px;

        text-align: center;

        color: #a18c7e;

        font-size: 11px;
    }

    .login-link a {
        color: #9b552e;

        font-weight: 700;

        text-decoration: none;
    }

    .login-link a:hover {
        text-decoration: underline;
    }

    /* SECURITY NOTE */

    .secure-note {
        display: flex;

        justify-content: center;
        align-items: center;

        gap: 7px;

        margin-top: 25px;

        color: #b3a193;

        font-size: 9px;

        letter-spacing: 0.5px;
    }

    .secure-note span {
        color: #9c7958;
        font-size: 12px;
    }

    /* RESPONSIVE */

    @media (max-width: 850px) {

        .register-wrapper {
            max-width: 480px;
            grid-template-columns: 1fr;
            min-height: auto;
        }

        .brand-panel {
            display: none;
        }

        .register-panel {
            padding: 45px 55px;
        }

    }

    @media (max-width: 500px) {

        body {
            padding: 18px 12px;
        }

        .register-wrapper {
            border-radius: 19px;
        }

        .register-panel {
            padding: 38px 25px;
        }

        .brand-name {
            font-size: 27px;
        }

        .welcome h2 {
            font-size: 27px;
        }

        .logo-area {
            margin-bottom: 25px;
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

    <div class="register-wrapper">

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
                    Dibuat dengan bahan pilihan, diproses dengan penuh perhatian, 
                    dan disajikan dengan cita rasa rumahan yang istimewa.
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


        <!-- RIGHT REGISTER PANEL -->

        <div class="register-panel">

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
                    CREATE ACCOUNT
                </div>

                <h2>Buat Akun</h2>

                <p>
                    Daftar untuk menikmati layanan Budiarti Risol.
                </p>

            </div>


            <!-- ERROR MESSAGE -->

            <?php if ($error != "") : ?>

                <div class="error">
                    ⚠ <?= htmlspecialchars($error); ?>
                </div>

            <?php endif; ?>


            <!-- REGISTER FORM -->

            <form method="POST">

                <div class="form-group">

                    <label for="nama">
                        Nama Lengkap
                    </label>

                    <div class="input-wrapper">

                        <span class="input-icon">♙</span>

                        <input
                            type="text"
                            id="nama"
                            name="nama"
                            placeholder="Masukkan nama lengkap"
                            value="<?= htmlspecialchars($_POST['nama'] ?? ''); ?>"
                            autocomplete="name"
                            required
                        >

                    </div>

                </div>


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
                            autocomplete="email"
                            required
                        >

                    </div>

                </div>


                <div class="form-group">

                    <label for="no_hp">
                        Nomor HP
                    </label>

                    <div class="input-wrapper">

                        <span class="input-icon">☎</span>

                        <input
                            type="tel"
                            id="no_hp"
                            name="no_hp"
                            placeholder="Masukkan nomor HP"
                            value="<?= htmlspecialchars($_POST['no_hp'] ?? ''); ?>"
                            autocomplete="tel"
                            required
                        >

                    </div>

                </div>


                <div class="form-group">

                    <label for="password">
                        Password
                    </label>

                    <div class="input-wrapper">

                        <span class="input-icon">●</span>

                        <input
                            type="password"
                            name="password"
                            id="password"
                            placeholder="Buat password"
                            autocomplete="new-password"
                            required
                        >

                        <button
                            type="button"
                            class="show-password"
                            onclick="togglePassword()"
                            id="passwordButton"
                        >
                            Lihat
                        </button>

                    </div>

                </div>


                <button
                    type="submit"
                    name="register"
                    class="btn-register"
                >
                    BUAT AKUN &nbsp; →
                </button>

            </form>


            <!-- LOGIN LINK -->

            <div class="login-link">

                Sudah punya akun?

                <a href="login.php">
                    Login di sini
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

        const button = document.getElementById("passwordButton");

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
