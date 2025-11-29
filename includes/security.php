<?php
// Güvenlik Fonksiyonları

// SQL Injection ve XSS koruması
function clean_input($data) {
    if (is_array($data)) {
        return array_map('clean_input', $data);
    }
    
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    
    return $data;
}

// Email validasyonu
function validate_email($email) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    
    // Email uzunluk kontrolü
    if (strlen($email) > 254) {
        return false;
    }
    
    return true;
}

// Şifre güçlülük kontrolü
function validate_password_strength($password) {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = "En az 8 karakter";
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "En az bir büyük harf";
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "En az bir küçük harf";
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "En az bir rakam";
    }
    
    // Zorunlu değil ama önerilen
    if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
        $errors[] = "Özel karakter önerilir";
    }
    
    return $errors;
}

// Kullanıcı adı validasyonu
function validate_username($username) {
    if (strlen($username) < 3 || strlen($username) > 20) {
        return false;
    }
    
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        return false;
    }
    
    // Rezerve edilmiş kelimeler
    $reserved_words = ['admin', 'administrator', 'root', 'system', 'user', 'test'];
    if (in_array(strtolower($username), $reserved_words)) {
        return false;
    }
    
    return true;
}

// CSRF Token oluşturma
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// CSRF Token doğrulama
function verify_csrf_token($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

// Rate limiting kontrolü
function check_rate_limit($key, $max_attempts = 5, $time_window = 900) {
    $current_time = time();
    
    if (!isset($_SESSION['rate_limits'][$key])) {
        $_SESSION['rate_limits'][$key] = [
            'attempts' => 0,
            'last_attempt' => $current_time
        ];
    }
    
    $rate_limit = $_SESSION['rate_limits'][$key];
    
    // Zaman penceresi dolduysa sıfırla
    if ($current_time - $rate_limit['last_attempt'] > $time_window) {
        $_SESSION['rate_limits'][$key] = [
            'attempts' => 0,
            'last_attempt' => $current_time
        ];
        return true;
    }
    
    // Deneme sayısı kontrolü
    if ($rate_limit['attempts'] >= $max_attempts) {
        return false;
    }
    
    return true;
}

// Rate limiting artırma
function increment_rate_limit($key) {
    if (!isset($_SESSION['rate_limits'][$key])) {
        $_SESSION['rate_limits'][$key] = [
            'attempts' => 0,
            'last_attempt' => time()
        ];
    }
    
    $_SESSION['rate_limits'][$key]['attempts']++;
    $_SESSION['rate_limits'][$key]['last_attempt'] = time();
}

// IP bazlı rate limiting
function get_client_ip() {
    $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
    
    foreach ($ip_keys as $key) {
        if (array_key_exists($key, $_SERVER)) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

// Güvenli header'lar
function set_security_headers() {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

// Input validation wrapper
function secure_input($input, $type = 'string') {
    $input = clean_input($input);
    
    switch ($type) {
        case 'email':
            if (!validate_email($input)) {
                return false;
            }
            break;
            
        case 'username':
            if (!validate_username($input)) {
                return false;
            }
            break;
            
        case 'password':
            // Password için özel temizleme gerekmez
            break;
            
        case 'int':
            $input = filter_var($input, FILTER_VALIDATE_INT);
            if ($input === false) {
                return false;
            }
            break;
            
        case 'float':
            $input = filter_var($input, FILTER_VALIDATE_FLOAT);
            if ($input === false) {
                return false;
            }
            break;
    }
    
    return $input;
}
?>