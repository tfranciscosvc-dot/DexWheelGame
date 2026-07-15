<?php
session_start();

// 1. Fetch Database Connection Parameters from Render Environment Variables
$db_host = getenv('DB_HOST');
$db_name = getenv('DB_NAME');
$db_user = getenv('DB_USER');
$db_pass = getenv('DB_PASS');
$db_port = getenv('DB_PORT') ?: '5432';

// 2. Fetch Admin Password from Render Environment Variables (Fallback for local testing)
define('ADMIN_PASSWORD', getenv('ADMIN_SECRET_KEY') ?: 'fallback_local_password');

try {
    // Connect to PostgreSQL Server
    $dsn = "pgsql:host=$db_host;port=$db_port;dbname=$db_name";
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // AUTOMATIC TABLE CREATION: Runs implicitly behind the scenes
    $sql = "CREATE TABLE IF NOT EXISTS spin_locks (
        id SERIAL PRIMARY KEY,
        email VARCHAR(255) UNIQUE NOT NULL,
        locked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );";
    $pdo->exec($sql);

} catch (PDOException $e) {
    die("Database connection failed configuration. Details: " . $e->getMessage());
}

// 3. Handle Admin Reset Trigger
if (isset($_GET['unlock']) && $_GET['unlock'] === 'true') {
    if (isset($_GET['password']) && $_GET['password'] === ADMIN_PASSWORD) {
        $_SESSION['has_spun'] = false;
        unset($_SESSION['user_email']);
        
        // Wipe all rows out of the database table entirely
        $pdo->exec("TRUNCATE TABLE spin_locks");
        
        header("Location: index.php?status=reset_success");
        exit;
    } else {
        die("Unauthorized access.");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lucky Spin Wheel</title>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.3/dist/confetti.browser.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f7f9fa;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
        }
        .game-container {
            text-align: center;
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            max-width: 400px;
            width: 100%;
        }
        .wheel-box {
            position: relative;
            width: 300px;
            height: 300px;
            margin: 20px auto;
        }
        #wheel {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            border: 5px solid #333;
            transition: transform 4s cubic-bezier(0.17, 0.67, 0.1, 1);
        }
        .pointer {
            position: absolute;
            top: -15px;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 0;
            border-left: 15px solid transparent;
            border-right: 15px solid transparent;
            border-top: 25px solid #e74c3c;
            z-index: 10;
        }
        .input-group {
            margin-bottom: 15px;
            text-align: left;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #444;
        }
        input[type="text"], input[type="email"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
        }
        button {
            background: #3498db;
            color: white;
            border: none;
            padding: 12px 20px;
            font-size: 16px;
            font-weight: bold;
            border-radius: 6px;
            cursor: pointer;
            width: 100%;
        }
        button:disabled {
            background: #bdc3c7;
            cursor: not-allowed;
        }
        #resultMessage {
            margin-top: 15px;
            font-size: 18px;
            font-weight: bold;
            min-height: 50px;
        }
    </style>
</head>
<body>

<div class="game-container">
    <h2>Spin & Win!</h2>
    
    <form id="spinForm">
        <div class="input-group">
            <label for="address">Wallet Address</label>
            <input type="text" id="address" placeholder="0x..." required>
        </div>
        <div class="input-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" placeholder="you@example.com" required>
        </div>
        <button type="submit" id="spinBtn">SPIN WHEEL</button>
    </form>

    <div class="wheel-box">
        <div class="pointer"></div>
        <div id="wheel" style="background: conic-gradient(#e74c3c 0deg 90deg, #f1c40f 90deg 180deg, #2ecc71 180deg 270deg, #9b59b6 270deg 360deg);"></div>
    </div>

    <div id="resultMessage"></div>
</div>

<script>
    const spinForm = document.getElementById('spinForm');
    const spinBtn = document.getElementById('spinBtn');
    const wheel = document.getElementById('wheel');
    const resultMessage = document.getElementById('resultMessage');

    // Setup potential prize slices matching wheel configurations
    const prizes = [0, 5, 0, 10]; 
    const degreesPerSlice = 360 / prizes.length;

    spinForm.addEventListener('submit', function(event) {
        event.preventDefault();

        const emailValue = document.getElementById('email').value;
        spinBtn.disabled = true;

        // 1. Live Check against the Database BEFORE initiating any animation
        fetch('lock.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email: emailValue })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success === false && data.error === 'already_locked') {
                // Deny entry completely
                resultMessage.textContent = "You have already spun the wheel! One entry per person.";
                resultMessage.style.color = "#e74c3c";
                spinBtn.disabled = false;
            } else if (data.success === true) {
                // Allowed to play! Start Wheel Math
                resultMessage.textContent = "Spinning...";
                resultMessage.style.color = "#333";

                // Generate random index outcome
                const randomIndex = Math.floor(Math.random() * prizes.length);
                const winningValue = prizes[randomIndex];

                // Calculate spin turns (extra rotations + specific slice offset)
                const extraRotations = 5 * 360; 
                const targetAngle = extraRotations + (randomIndex * degreesPerSlice) + (degreesPerSlice / 2);

                // Run CSS rotation transition
                wheel.style.transform = `rotate(${targetAngle}deg)`;

                // 2. Handle Actions after 4s CSS transition completes
                setTimeout(() => {
                    resultMessage.textContent = `Congratulations! You won: $${winningValue}`;
                    resultMessage.style.color = winningValue > 0 ? "#27ae60" : "#e74c3c";

                    // Burst colorful confetti if prize value is greater than zero 🎉
                    if (winningValue > 0) {
                        confetti({
                            particleCount: 150,
                            spread: 80,
                            origin: { y: 0.6 }
                        });

                        // Direct link text underneath
                        resultMessage.innerHTML += `<br><span style="font-size: 13px; color: #666; font-weight: normal; display: block; margin-top: 5px;">check email for confirmation</span>`;
                        
                        // Automatically route player to your confirm page after 3 seconds
                        setTimeout(() => {
                            window.location.href = "confirm.html";
                        }, 3000);
                    }

                    // Prepare payload to send out data tracking record via Web3Forms
                    const formData = {
                        address: document.getElementById('address').value,
                        email: emailValue,
                        prize: winningValue
                    };

                    // Send payload off to email API
                    sendResultEmail(formData);

                }, 4000);

            } else {
                alert("Server encountered an issue syncing status. Please retry.");
                spinBtn.disabled = false;
            }
        })
        .catch(err => {
            console.error("Locking sync failed", err);
            spinBtn.disabled = false;
        });
    });

    // Mock wrapper function for your Web3Forms engine context
    function sendResultEmail(data) {
        console.log("Transmitting data context payload to Web3Forms API:", data);
        // Put your existing Web3Forms fetch function code right here if needed
    }
</script>

</body>
</html>
