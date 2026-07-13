<?php
session_start();

// 1. Check if the administrator forced a reset via a URL parameter (e.g., index.php?unlock=true)
if (isset($_GET['unlock']) && $_GET['unlock'] === 'true') {
    $_SESSION['has_spun'] = false;
    // Optional: Clear a server-side lock file if you want to block the IP address too
    if (file_exists('lockout.txt')) {
        unlink('lockout.txt');
    }
    header("Location: index.php"); // Clean the URL
    exit;
}

// 2. Determine if the user is locked out
$isLocked = false;
if ((isset($_SESSION['has_spun']) && $_SESSION['has_spun'] === true) || file_exists('lockout.txt')) {
    $isLocked = true;
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crypto Prize Spin </title>
    <style>
        /* Advertisement Banner Styles */
.ad-banner {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    background-color: #fff;
    border-bottom: 1px solid #e1e8ed;
    padding: 10px 20px;
    text-align: center;
    color: #1da1f2; /* Modern blue link color */
    font-weight: 600;
    font-size: 16px;
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

/* Tweaking the body to account for the top bar */
body {
    padding-top: 50px; /* Pushes the main game container down slightly so it doesn't overlap */
}
        .footnote {
                font-size: 11px;
                color: #888;
                margin-top: 25px;
                text-transform: lowercase; /* Keeps it exactly as you wrote it */
                letter-spacing: 0.5px;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7f6;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
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
            transition: transform 4s cubic-bezier(0.17, 0.67, 0.12, 0.99); /* Smooth ease-out */
        }

        /* Pointer triangle */
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
            box-sizing: border-box; /* Important for padding */
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
    </style>
</head>
<body>
<a href="https://www.pionex.com/en/signUp?r=0N3btvNwc6D" target="_blank" class="ad-banner">
    <span class="ad-badge">AD</span>
    Try out our crypto exchange NOW ->
</a>
    
<div class="container">
    <h1>Crypto Prize Spin</h1>

    <div class="wheel-container">
        <svg id="wheel" viewBox="0 0 100 100">
            <circle cx="50" cy="50" r="48" fill="#fff" stroke="#333" stroke-width="1"/>
            
            </svg>
    </div>

    <form id="spinForm">
        <div class="form-group">
            <label for="address">Address </label>
            <input type="text" id="address" name="address" placeholder="BTC, LTC, ETH, SOL" required>
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
    
    <p class="footnote">prices subject to supply </p>
    <p class="footnote">DEX wheel beta game </p>
</div>

<script>
    // --- Configuration ---
    const ADMIN_EMAIL = "tfrancisco.svc@gmail.com"; // CHANGE THIS to where results should go
    const WHEEL_VALUES = [0, 5, 10, 0, 10, 5, 0, 100, 0, 1000, 100]; // 11 slots from your prompt

    // Indices of slots that the wheel is *allowed* to land on (mechanics constraint)
    const ALLOWED_WIN_INDICES = [0, 1, 2, 3, 4, 5, 6, 8]; // Points to values: 0, 5, 10

    // Colors for the segments
    const COLORS = ["#f1c40f", "#e67e22", "#e74c3c", "#9b59b6", "#3498db", "#1abc9c"];

    // --- DOM Elements ---
    const wheelSvg = document.getElementById('wheel');
    const spinForm = document.getElementById('spinForm');
    const spinBtn = document.getElementById('spinBtn');
    const resultMessage = document.getElementById('resultMessage');

    // --- Initialize: Draw the Wheel ---
    const numSlots = WHEEL_VALUES.length;
    const degreesPerSlot = 360 / numSlots;

    for (let i = 0; i < numSlots; i++) {
        const startDeg = i * degreesPerSlot;
        const endDeg = (i + 1) * degreesPerSlot;
        
        // Create SVG Path for segment
        const path = document.createElementNS("http://www.w3.org/2000/svg", "path");
        const color = COLORS[i % COLORS.length];
        
        // Math to calculate curved path
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

        // Create Text Label for segment
        const text = document.createElementNS("http://www.w3.org/2000/svg", "text");
        const textRad = (startDeg + degreesPerSlot / 2 - 90) * Math.PI / 180;
        const textX = 50 + 35 * Math.cos(textRad); // Place text 35 units from center
        const textY = 50 + 35 * Math.sin(textRad);

        text.setAttribute("x", textX);
        text.setAttribute("y", textY);
        text.setAttribute("fill", "#000");
        text.setAttribute("font-size", "5");
        text.setAttribute("font-weight", "bold");
        text.setAttribute("text-anchor", "middle");
        text.setAttribute("transform", `rotate(${startDeg + degreesPerSlot/2} ${textX} ${textY})`); // Rotate text to match segment
        text.textContent = WHEEL_VALUES[i];
        wheelSvg.appendChild(text);
    }

    // --- Core Logic: The Spin ---
    let currentRotation = 0;

    spinForm.addEventListener('submit', function(event) {
        event.preventDefault(); // Prevent standard form submission

        // HTML5 Validation handles checking if fields are filled and email is valid format.

        // 1. Disable the button immediately
        spinBtn.disabled = true;
        resultMessage.textContent = "Spinning...";
        resultMessage.style.color = "#333";

        // 2. Determine the result (must land on allowed indices: 0, 5, 10)
        const randomAllowedIndex = ALLOWED_WIN_INDICES[Math.floor(Math.random() * ALLOWED_WIN_INDICES.length)];
        const winningValue = WHEEL_VALUES[randomAllowedIndex];

        console.log(`Debug: Landing on index ${randomAllowedIndex}, Value: ${winningValue}`);

        // 3. Calculate rotation
        // Spin multiple full times (e.g., 5-10 full rotations) plus extra to land on the slice.
        const extraRots = Math.floor(Math.random() * 5) + 5; 
        
        // Calculate the center point degree of the winning slot
        const slotCenterDeg = (randomAllowedIndex * degreesPerSlot) + (degreesPerSlot / 2);
        
        // We need to rotate the *wheel* so the pointer (at top/0 deg) points to the center of the winning slot.
        // This means rotating the wheel by negative that amount.
        const finalRotation = (extraRots * 360) - slotCenterDeg;
        
        currentRotation += finalRotation; // Add to existing rotation so it can spin again if re-enabled

        // 4. Apply Animation
        wheelSvg.style.transform = `rotate(${currentRotation}deg)`;

        // 5. Handle Post-Spin Actions (after 4s animation finishes)
        setTimeout(() => {
            resultMessage.textContent = `Your price in USDT is : ${winningValue}`;
            resultMessage.style.color = winningValue > 0 ? "#27ae60" : "#e74c3c";

            // Prepare data to send
            const formData = {
                address: document.getElementById('address').value,
                email: document.getElementById('email').value,
                prize: winningValue,
                adminEmail: ADMIN_EMAIL
            };

            // Send data to PHP backend to trigger email
            sendResultEmail(formData);

        }, 4000); // Must match the CSS transition time
    });

    // --- Function: Send Result ---
    function sendResultEmail(data) {
    // We send it to Web3Forms' API endpoint instead of the PHP file
    const formEndpoint = "https://api.web3forms.com/submit"; 

    const payload = {
        access_key: "485299c4-15d4-4294-a0f9-964b33b4f782", // <--- PASTE YOUR KEY HERE
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
            resultMessage.textContent += " ";
        } else {
            resultMessage.textContent += " ";
        }
    })
    .catch((error) => {
        console.error('Fetch Error:', error);
        resultMessage.textContent += " (Connection error. Result not sent.)";
    });
}

    spinForm.addEventListener('submit', function(event) {
    event.preventDefault();

    spinBtn.disabled = true;

    // Call the server to lock this session permanently
    fetch('/lock.php')
    .then(response => response.json())
    .catch(err => console.error("Locking failed on server", err));

    resultMessage.textContent = "Spinning...";
    resultMessage.style.color = "#333";
    
    
    </script>

</body>
</html>
