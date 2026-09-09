<?php
/**
 * Bilge Asistan
 * RAG mimarili sohbet botu
 *
 * @author Neşe Sağır
 */

// OpenAI API anahtarı
$api_key = "BURAYA_API_ANAHTARI_GELECEK";

// Veritabanı ayarları
$db_host = 'localhost';
$db_name = 'llm_proje';
$db_user = 'root';
$db_pass = '';

function getDbConnection(): PDO
{
    global $db_host, $db_name, $db_user, $db_pass;

    $dsn = "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    return new PDO($dsn, $db_user, $db_pass, $options);
}

function fetchKnowledgeContext(PDO $pdo): string
{
    $stmt = $pdo->query("SELECT icerik FROM bilgi_bankasi WHERE icerik IS NOT NULL AND icerik != ''");
    $rows = $stmt->fetchAll();

    if (empty($rows)) {
        return '';
    }

    $parts = [];
    foreach ($rows as $index => $row) {
        $parts[] = '--- Kayıt ' . ($index + 1) . " ---\n" . trim($row['icerik']);
    }

    return implode("\n\n", $parts);
}

function askOpenAI(string $apiKey, string $context, string $userMessage): array
{
    $systemPrompt = "Sen Bilge Asistan'sın. Yanıtlarını öncelikle sağlanan bilgi bankası verilerine dayandır. "
        . "Bilgi bankasında yanıt yoksa genel bilginle doğal ve net bir şekilde yanıt ver.";

    $userPrompt = "Bilgi Bankası:\n" . ($context !== '' ? $context : '(Bilgi bankası boş)') . "\n\n"
        . "Kullanıcı Sorusu:\n" . $userMessage;

    $payload = [
        'model'    => 'gpt-3.5-turbo',
        'messages' => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt],
        ],
        'temperature' => 0.7,
        'max_tokens'  => 1000,
    ];

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ],
        CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT        => 60,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        return ['success' => false, 'error' => 'cURL hatası: ' . $curlError];
    }

    $data = json_decode($response, true);

    if ($httpCode !== 200) {
        $errorMsg = $data['error']['message'] ?? 'Bilinmeyen API hatası (HTTP ' . $httpCode . ')';
        return ['success' => false, 'error' => $errorMsg];
    }

    $reply = $data['choices'][0]['message']['content'] ?? null;

    if ($reply === null) {
        return ['success' => false, 'error' => 'API yanıtı işlenemedi.'];
    }

    return ['success' => true, 'reply' => trim($reply)];
}

// API isteklerini işle
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    $input = json_decode(file_get_contents('php://input'), true);
    $message = trim($input['message'] ?? '');

    if ($message === '') {
        echo json_encode(['success' => false, 'error' => 'Lütfen bir mesaj yazın.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($api_key === '' || $api_key === 'BURAYA_API_ANAHTARI_GELECEK') {
        echo json_encode(['success' => false, 'error' => 'OpenAI API anahtarı tanımlanmamış. Lütfen dosyanın üst kısmındaki $api_key değişkenini güncelleyin.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    try {
        $pdo = getDbConnection();
        $context = fetchKnowledgeContext($pdo);
        $result = askOpenAI($api_key, $context, $message);
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Veritabanı hatası: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Hata: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }

    exit;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bilge Asistan</title>
    <style>
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --bg: #0f172a;
            --surface: #1e293b;
            --surface-light: #334155;
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --user-bubble: #6366f1;
            --bot-bubble: #1e293b;
            --text: #f1f5f9;
            --text-muted: #94a3b8;
            --border: #334155;
            --shadow: 0 4px 24px rgba(0, 0, 0, 0.3);
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            background-image:
                radial-gradient(ellipse at 20% 0%, rgba(99, 102, 241, 0.15) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 100%, rgba(139, 92, 246, 0.1) 0%, transparent 50%);
        }

        .chat-container {
            width: 100%;
            max-width: 720px;
            height: 90vh;
            max-height: 800px;
            background: var(--surface);
            border-radius: 20px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .chat-header {
            padding: 1.25rem 1.5rem;
            background: linear-gradient(135deg, var(--primary) 0%, #8b5cf6 100%);
            display: flex;
            align-items: center;
            gap: 0.875rem;
        }

        .chat-header-icon {
            width: 42px;
            height: 42px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }

        .chat-header-text h1 {
            font-size: 1.125rem;
            font-weight: 600;
        }

        .chat-header-text p {
            font-size: 0.8rem;
            opacity: 0.85;
            margin-top: 0.125rem;
        }

        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            scroll-behavior: smooth;
        }

        .chat-messages::-webkit-scrollbar {
            width: 6px;
        }

        .chat-messages::-webkit-scrollbar-thumb {
            background: var(--surface-light);
            border-radius: 3px;
        }

        .message {
            display: flex;
            gap: 0.625rem;
            max-width: 85%;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .message.user {
            align-self: flex-end;
            flex-direction: row-reverse;
        }

        .message.bot {
            align-self: flex-start;
        }

        .message-avatar {
            width: 32px;
            height: 32px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
            flex-shrink: 0;
        }

        .message.user .message-avatar {
            background: var(--user-bubble);
        }

        .message.bot .message-avatar {
            background: var(--surface-light);
        }

        .message-bubble {
            padding: 0.75rem 1rem;
            border-radius: 16px;
            line-height: 1.55;
            font-size: 0.9375rem;
            word-wrap: break-word;
            white-space: pre-wrap;
        }

        .message.user .message-bubble {
            background: var(--user-bubble);
            border-bottom-right-radius: 4px;
        }

        .message.bot .message-bubble {
            background: var(--bot-bubble);
            border: 1px solid var(--border);
            border-bottom-left-radius: 4px;
        }

        .message.error .message-bubble {
            background: #7f1d1d;
            border-color: #991b1b;
        }

        .typing-indicator {
            display: flex;
            gap: 4px;
            padding: 0.875rem 1rem;
            background: var(--bot-bubble);
            border: 1px solid var(--border);
            border-radius: 16px;
            border-bottom-left-radius: 4px;
        }

        .typing-indicator span {
            width: 8px;
            height: 8px;
            background: var(--text-muted);
            border-radius: 50%;
            animation: bounce 1.4s infinite ease-in-out both;
        }

        .typing-indicator span:nth-child(1) { animation-delay: -0.32s; }
        .typing-indicator span:nth-child(2) { animation-delay: -0.16s; }

        @keyframes bounce {
            0%, 80%, 100% { transform: scale(0.6); opacity: 0.4; }
            40%           { transform: scale(1); opacity: 1; }
        }

        .chat-input-area {
            padding: 1rem 1.25rem;
            border-top: 1px solid var(--border);
            background: rgba(15, 23, 42, 0.5);
        }

        .chat-form {
            display: flex;
            gap: 0.625rem;
            align-items: flex-end;
        }

        .chat-form textarea {
            flex: 1;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 0.75rem 1rem;
            color: var(--text);
            font-family: inherit;
            font-size: 0.9375rem;
            resize: none;
            min-height: 48px;
            max-height: 120px;
            line-height: 1.4;
            transition: border-color 0.2s;
        }

        .chat-form textarea:focus {
            outline: none;
            border-color: var(--primary);
        }

        .chat-form textarea::placeholder {
            color: var(--text-muted);
        }

        .send-btn {
            width: 48px;
            height: 48px;
            border: none;
            border-radius: 14px;
            background: var(--primary);
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s, transform 0.1s;
            flex-shrink: 0;
        }

        .send-btn:hover:not(:disabled) {
            background: var(--primary-hover);
        }

        .send-btn:active:not(:disabled) {
            transform: scale(0.95);
        }

        .send-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .send-btn svg {
            width: 20px;
            height: 20px;
        }

        .welcome-message {
            text-align: center;
            color: var(--text-muted);
            padding: 2rem 1rem;
            font-size: 0.9rem;
        }

        .welcome-message strong {
            display: block;
            color: var(--text);
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="chat-container">
        <header class="chat-header">
            <div class="chat-header-icon">🤖</div>
            <div class="chat-header-text">
                <h1>Bilge Asistan</h1>
                <p>Sorgulayın, keşfedin, öğrenin</p>
            </div>
        </header>

        <div class="chat-messages" id="chatMessages">
            <div class="welcome-message">
                <strong>Merhaba!</strong>
                Bilgi bankasındaki verilere dayanarak sorularınızı yanıtlayabilirim. Nasıl yardımcı olabilirim?
            </div>
        </div>

        <div class="chat-input-area">
            <form class="chat-form" id="chatForm">
                <textarea
                    id="messageInput"
                    placeholder="Mesajınızı yazın..."
                    rows="1"
                    autocomplete="off"
                ></textarea>
                <button type="submit" class="send-btn" id="sendBtn" title="Gönder">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="22" y1="2" x2="11" y2="13"></line>
                        <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                    </svg>
                </button>
            </form>
        </div>
    </div>

    <script>
        const chatMessages = document.getElementById('chatMessages');
        const chatForm = document.getElementById('chatForm');
        const messageInput = document.getElementById('messageInput');
        const sendBtn = document.getElementById('sendBtn');

        messageInput.addEventListener('input', function () {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 120) + 'px';
        });

        messageInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                chatForm.dispatchEvent(new Event('submit'));
            }
        });

        function removeWelcome() {
            const welcome = chatMessages.querySelector('.welcome-message');
            if (welcome) welcome.remove();
        }

        function appendMessage(content, type) {
            removeWelcome();

            const msg = document.createElement('div');
            msg.className = 'message ' + type;

            const avatar = document.createElement('div');
            avatar.className = 'message-avatar';
            avatar.textContent = type === 'user' ? '👤' : '🤖';

            const bubble = document.createElement('div');
            bubble.className = 'message-bubble';
            bubble.textContent = content;

            msg.appendChild(avatar);
            msg.appendChild(bubble);
            chatMessages.appendChild(msg);
            chatMessages.scrollTop = chatMessages.scrollHeight;

            return msg;
        }

        function showTyping() {
            removeWelcome();

            const msg = document.createElement('div');
            msg.className = 'message bot';
            msg.id = 'typingIndicator';

            const avatar = document.createElement('div');
            avatar.className = 'message-avatar';
            avatar.textContent = '🤖';

            const typing = document.createElement('div');
            typing.className = 'typing-indicator';
            typing.innerHTML = '<span></span><span></span><span></span>';

            msg.appendChild(avatar);
            msg.appendChild(typing);
            chatMessages.appendChild(msg);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        function hideTyping() {
            const el = document.getElementById('typingIndicator');
            if (el) el.remove();
        }

        chatForm.addEventListener('submit', async function (e) {
            e.preventDefault();

            const message = messageInput.value.trim();
            if (!message) return;

            appendMessage(message, 'user');
            messageInput.value = '';
            messageInput.style.height = 'auto';

            sendBtn.disabled = true;
            showTyping();

            try {
                const response = await fetch('index.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ message: message })
                });

                const data = await response.json();
                hideTyping();

                if (data.success) {
                    appendMessage(data.reply, 'bot');
                } else {
                    const errMsg = document.createElement('div');
                    errMsg.className = 'message bot error';
                    errMsg.innerHTML = '<div class="message-avatar">⚠️</div><div class="message-bubble">' + data.error + '</div>';
                    chatMessages.appendChild(errMsg);
                    chatMessages.scrollTop = chatMessages.scrollHeight;
                }
            } catch (err) {
                hideTyping();
                appendMessage('Bağlantı hatası oluştu. Lütfen tekrar deneyin.', 'bot error');
            }

            sendBtn.disabled = false;
            messageInput.focus();
        });
    </script>
</body>
</html>

