<?php
/**
 * Simple SMTP Mail Class
 * Sends emails via Gmail SMTP without external dependencies
 */

class SmtpMail {
    private $smtp_host;
    private $smtp_port;
    private $smtp_user;
    private $smtp_pass;
    private $smtp_secure;
    private $from_email;
    private $from_name;
    private $socket;
    private $debug = false;
    private $last_error = '';

    public function __construct($config = []) {
        $this->smtp_host = $config['host'] ?? 'smtp.gmail.com';
        $this->smtp_port = $config['port'] ?? 587;
        $this->smtp_user = $config['username'] ?? '';
        $this->smtp_pass = $config['password'] ?? '';
        $this->smtp_secure = $config['secure'] ?? 'tls';
        $this->from_email = $config['from_email'] ?? $this->smtp_user;
        $this->from_name = $config['from_name'] ?? 'Basils Money Lenders';
        $this->debug = $config['debug'] ?? false;
    }

    public function send($to_email, $to_name, $subject, $html_body) {
        if (empty($this->smtp_user) || empty($this->smtp_pass)) {
            $this->last_error = 'SMTP credentials not configured';
            return false;
        }

        try {
            // Connect to SMTP server
            $this->socket = @fsockopen(
                ($this->smtp_secure == 'ssl' ? 'ssl://' : '') . $this->smtp_host,
                $this->smtp_port,
                $errno,
                $errstr,
                30
            );

            if (!$this->socket) {
                $this->last_error = "Connection failed: $errstr ($errno)";
                return false;
            }

            // Set timeout
            stream_set_timeout($this->socket, 30);

            // Read greeting
            $this->getResponse();

            // Say hello
            $this->sendCommand("EHLO " . gethostname());

            // Start TLS if needed
            if ($this->smtp_secure == 'tls') {
                $this->sendCommand("STARTTLS");
                stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                $this->sendCommand("EHLO " . gethostname());
            }

            // Authenticate
            $this->sendCommand("AUTH LOGIN");
            $this->sendCommand(base64_encode($this->smtp_user));
            $this->sendCommand(base64_encode($this->smtp_pass));

            // Set sender
            $this->sendCommand("MAIL FROM: <{$this->from_email}>");

            // Set recipient
            $this->sendCommand("RCPT TO: <{$to_email}>");

            // Send data
            $this->sendCommand("DATA");

            // Build message
            $boundary = md5(uniqid(time()));
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "From: {$this->from_name} <{$this->from_email}>\r\n";
            $headers .= "To: {$to_name} <{$to_email}>\r\n";
            $headers .= "Subject: {$subject}\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "X-Mailer: BrianInvestments-SMTP/1.0\r\n";

            $message = $headers . "\r\n" . $html_body . "\r\n.";
            $this->sendCommand($message, false);

            // Quit
            $this->sendCommand("QUIT");

            fclose($this->socket);
            return true;

        } catch (Exception $e) {
            $this->last_error = $e->getMessage();
            if ($this->socket) {
                fclose($this->socket);
            }
            return false;
        }
    }

    private function sendCommand($command, $expect_response = true) {
        if ($this->debug) {
            error_log("SMTP > " . substr($command, 0, 50) . "...");
        }

        fwrite($this->socket, $command . "\r\n");

        if ($expect_response) {
            $response = $this->getResponse();
            if ($this->debug) {
                error_log("SMTP < " . $response);
            }

            // Check for error codes (4xx and 5xx)
            $code = substr($response, 0, 3);
            if ($code[0] == '4' || $code[0] == '5') {
                throw new Exception("SMTP Error: $response");
            }
            return $response;
        }
        return true;
    }

    private function getResponse() {
        $response = '';
        while (($line = fgets($this->socket, 515)) !== false) {
            $response .= $line;
            // Check if this is the last line (space after code means more lines coming)
            if (substr($line, 3, 1) == ' ') {
                break;
            }
        }
        return trim($response);
    }

    public function getLastError() {
        return $this->last_error;
    }
}

/**
 * Get SMTP configuration from database or config file
 */
function get_smtp_config($conn = null) {
    // Try to get from database first
    if ($conn) {
        try {
            // Check if table exists first
            $tableCheck = @$conn->query("SHOW TABLES LIKE 'system_settings'");
            if ($tableCheck && $tableCheck->num_rows > 0) {
                $result = @$conn->query("SELECT * FROM system_settings WHERE id = 1");
                if ($result && $row = $result->fetch_assoc()) {
                    if (!empty($row['smtp_host']) && !empty($row['smtp_user'])) {
                        return [
                            'host' => $row['smtp_host'],
                            'port' => $row['smtp_port'] ?? 587,
                            'username' => $row['smtp_user'],
                            'password' => $row['smtp_pass'],
                            'secure' => $row['smtp_secure'] ?? 'tls',
                            'from_email' => $row['smtp_from_email'] ?? $row['smtp_user'],
                            'from_name' => $row['smtp_from_name'] ?? 'Basils Money Lenders'
                        ];
                    }
                }
            }
        } catch (Exception $e) {
            // Table doesn't exist or query failed, fall back to config file
        }
    }

    // Fallback to config file
    $config_file = __DIR__ . '/../config.php';
    if (file_exists($config_file)) {
        include $config_file;
        if (defined('SMTP_HOST') && defined('SMTP_USER') && defined('SMTP_PASS')) {
            return [
                'host' => SMTP_HOST,
                'port' => defined('SMTP_PORT') ? SMTP_PORT : 587,
                'username' => SMTP_USER,
                'password' => SMTP_PASS,
                'secure' => defined('SMTP_SECURE') ? SMTP_SECURE : 'tls',
                'from_email' => defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : SMTP_USER,
                'from_name' => defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'Basils Money Lenders'
            ];
        }
    }

    return null;
}

/**
 * Send email using SMTP
 */
function smtp_send_email($to_email, $to_name, $subject, $html_body, $conn = null) {
    $config = get_smtp_config($conn);

    if (!$config) {
        error_log("SMTP not configured - email not sent to: $to_email");
        return false;
    }

    $smtp = new SmtpMail($config);
    $result = $smtp->send($to_email, $to_name, $subject, $html_body);

    if (!$result) {
        error_log("SMTP Error: " . $smtp->getLastError());
    }

    return $result;
}
