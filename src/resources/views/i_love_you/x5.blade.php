<!doctype html>
<html lang="bn">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <title>আমার জানের জন্য ❤️</title>
    <meta name="title" content="আমার জানের জন্য ❤️">
    <meta name="description" content="একটা ছোট সারপ্রাইজ যা আমি আমার জানের জন্য বানিয়েছি... ❤️">
    <link rel="icon" href="{{ get_image('app_ico') ?? asset('assets/img/favicon/Encodex.ico') }}" type="image/x-icon">
    <link rel="apple-touch-icon" href="{{ get_image('app_ico') ?? asset('assets/img/favicon/Encodex.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;600;700&family=Great+Vibes&display=swap" rel="stylesheet" />
    <style>
        :root {
            --primary: #ff4d6d;
            --secondary: #ff8fa3;
            --bg-gradient: linear-gradient(135deg, #fff0f3 0%, #ffe5ec 100%);
            --glass: rgba(255, 255, 255, 0.95);
            --shadow: 0 20px 40px rgba(255, 77, 109, 0.15);
            --text-color: #555;
            }
            * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
            }
            body {
            font-family: "Hind Siliguri", sans-serif;
            background: var(--bg-gradient);
            height: 100vh;
            width: 100vw;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            position: relative;
            }

            /* Background Elements */
            .floating-emoji {
            position: absolute;
            bottom: -50px;
            font-size: 24px;
            animation: floatUp linear forwards;
            pointer-events: none;
            z-index: 1;
            opacity: 0.6;
            }
            @keyframes floatUp {
            0% { transform: translateY(0) rotate(0deg); opacity: 0; }
            10% { opacity: 0.8; }
            100% { transform: translateY(-110vh) rotate(360deg); opacity: 0; }
            }

            /* Main Glass Card */
            .card {
            background: var(--glass);
            border-radius: 30px;
            box-shadow: var(--shadow);
            width: 90%;
            max-width: 450px;
            padding: 2.5rem 1.5rem;
            text-align: center;
            position: relative;
            z-index: 10;
            display: flex;
            flex-direction: column;
            align-items: center;
            border: 2px solid rgba(255, 255, 255, 0.8);
            transition: all 0.5s ease;
            }

            /* Lock Screen Styles */
            #lock-screen {
            display: flex;
            flex-direction: column;
            align-items: center;
            }
            .lock-title {
            color: var(--primary);
            font-size: 1.8rem;
            margin-bottom: 1.5rem;
            font-weight: 700;
            }
            .date-inputs {
            display: flex;
            gap: 10px;
            margin-bottom: 1.5rem;
            justify-content: center;
            }
            .date-field {
            width: 60px;
            height: 60px;
            text-align: center;
            font-size: 1.2rem;
            border: 2px solid var(--secondary);
            border-radius: 12px;
            outline: none;
            color: var(--primary);
            font-weight: bold;
            transition: 0.3s;
            }
            .date-field:focus {
            border-color: var(--primary);
            box-shadow: 0 0 10px rgba(255, 77, 109, 0.3);
            }

            /* Cat SVG Styles */
            .cat-container {
            width: 160px;
            height: 140px;
            margin-bottom: 20px;
            position: relative;
            }
            .cat-svg {
            width: 100%;
            height: 100%;
            animation: breathe 3s ease-in-out infinite;
            }
            @keyframes breathe {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
            }

            /* Content Styles */
            #main-content {
            display: none;
            width: 100%;
            animation: fadeIn 0.8s ease-out;
            }
            @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
            }
            h1 {
            color: var(--primary);
            font-size: 2.2rem;
            margin-bottom: 1.5rem;
            line-height: 1.4;
            }
            p {
            color: var(--text-color);
            font-size: 1.2rem;
            margin-bottom: 2rem;
            line-height: 1.6;
            }

            /* Buttons */
            .btn-group {
            display: flex;
            gap: 15px;
            justify-content: center;
            width: 100%;
            flex-wrap: wrap;
            }
            .btn {
            padding: 12px 30px;
            font-family: inherit;
            font-weight: 700;
            font-size: 1.1rem;
            border-radius: 50px;
            border: none;
            cursor: pointer;
            transition: transform 0.2s;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            }
            .btn-primary {
            background: linear-gradient(45deg, var(--primary), var(--secondary));
            color: white;
            }
            .btn-secondary {
            background: white;
            color: var(--primary);
            border: 2px solid var(--secondary);
            }
            .btn:active { transform: scale(0.9); }

            /* Final Message Screen */
            #final-screen {
            display: none;
            animation: fadeIn 0.8s ease-out;
            }

            .hidden { display: none !important; }
        </style>
  </head>
  <body>

    <!-- Lock Screen -->
    <div class="card" id="lock-card">
      <div id="lock-screen">
        <div class="lock-title">আমাদের বিয়ের দিনটি লিখো ❤️</div>
        <div class="date-inputs">
          <input type="number" class="date-field" id="day" placeholder="দিন" maxlength="2">
          <input type="number" class="date-field" id="month" placeholder="মাস" maxlength="2">
          <input type="number" class="date-field" id="year" placeholder="বছর" maxlength="4">
        </div>
        <button class="btn btn-primary" onclick="unlock()">আনলক করো 🔓</button>
        <p id="error-msg" style="color: red; font-size: 0.9rem; margin-top: 10px; display: none;">সঠিক তারিখটি দাও জান! 🥺</p>
      </div>
    </div>

    <!-- Main Content Card -->
    <div class="card hidden" id="main-card">
      <div class="cat-container">
        <svg class="cat-svg" viewBox="0 0 200 180" xmlns="http://www.w3.org/2000/svg">
          <ellipse cx="100" cy="140" rx="60" ry="40" fill="#fff" stroke="#eee" stroke-width="2" />
          <circle cx="100" cy="90" r="50" fill="#fff" stroke="#eee" stroke-width="2" />
          <path d="M65 60 L50 20 L90 50 Z" fill="#fff" stroke="#eee" stroke-width="2" />
          <path d="M135 60 L150 20 L110 50 Z" fill="#fff" stroke="#eee" stroke-width="2" />
          <path d="M70 58 L60 30 L85 52 Z" fill="#ffccd5" />
          <path d="M130 58 L140 30 L115 52 Z" fill="#ffccd5" />
          <circle cx="75" cy="95" r="5" fill="#ffaec9" opacity="0.6" />
          <circle cx="125" cy="95" r="5" fill="#ffaec9" opacity="0.6" />
          <g>
            <circle cx="85" cy="85" r="4" fill="#333" />
            <circle cx="115" cy="85" r="4" fill="#333" />
          </g>
          <path d="M95 100 Q100 105 105 100" stroke="#333" stroke-width="2" fill="none" stroke-linecap="round" />
          <circle cx="70" cy="140" r="15" fill="#fff" stroke="#eee" />
          <circle cx="130" cy="140" r="15" fill="#fff" stroke="#eee" />
        </svg>
      </div>

      <div id="main-content">
        <h1>জান তুমি কি আমাকে ভালোবাসো? ❤️</h1>
        <p>আমি তোমার উত্তরের অপেক্ষায় আছি...</p>
        <div class="btn-group">
          <button class="btn btn-primary" id="yesBtn">হ্যাঁ</button>
          <button class="btn btn-secondary" id="noBtn" onclick="showFinal()">না</button>
        </div>
      </div>

      <div id="final-screen">
        <h1>আই লাভ ইউ জান! ❤️</h1>
        <p>জান তুমি "না" করেছো কিন্তু আমি জানি যে তুমি আমাকে কতটা ভালোবাসো... <br> <b>আমি তোমাকে অনেক অনেক ভালোবাসি!</b></p>
        <button class="btn btn-primary" onclick="location.reload()">আবার শুরু করি</button>
      </div>
    </div>

    <script>
        // Lock Screen Logic
        function unlock() {
            const d = document.getElementById('day').value;
            const m = document.getElementById('month').value;
            const y = document.getElementById('year').value;
            const error = document.getElementById('error-msg');

            // Date: 30/05/2026
            if (parseInt(d) === 30 && parseInt(m) === 5 && parseInt(y) === 2026) {
                document.getElementById('lock-card').classList.add('hidden');
                document.getElementById('main-card').classList.remove('hidden');
                document.getElementById('main-content').style.display = 'block';
                startFloatingHearts();
            } else {
                error.style.display = 'block';
            }
        }

        // "Yes" Button Dodge Logic (Trick)
        const yesBtn = document.getElementById("yesBtn");
        let dodgeCount = 0;
        const dodgeTexts = ["না, এখানে না!", "ধরতে পারবে না!", "একটু চেষ্টা করো!", "আরে এখানে!", "পারবে না জান!", "হাহাহা!"];

        function dodgeBtn(e) {
            // Prevent default for touch to avoid jumping
            if (e.type === "touchstart") e.preventDefault();
            
            const margin = 30;
            const maxX = window.innerWidth - yesBtn.offsetWidth - margin;
            const maxY = window.innerHeight - yesBtn.offsetHeight - margin;
            
            const randomX = Math.max(margin, Math.random() * maxX);
            const randomY = Math.max(margin, Math.random() * maxY);

            yesBtn.style.position = "fixed";
            yesBtn.style.left = randomX + "px";
            yesBtn.style.top = randomY + "px";
            yesBtn.innerText = dodgeTexts[dodgeCount % dodgeTexts.length];
            dodgeCount++;
        }

        ["mouseover", "touchstart", "click"].forEach((evt) => yesBtn.addEventListener(evt, dodgeBtn));

        // Final Screen Logic
        function showFinal() {
            document.getElementById('main-content').style.display = 'none';
            document.getElementById('final-screen').style.display = 'block';
            
            // Extra heart burst
            for(let i=0; i<20; i++) createFloatingEmoji(true);
        }

        // Background Hearts
        function createFloatingEmoji(isInstant = false) {
            const emojis = ["❤️", "💖", "✨", "🌸", "💕"];
            const el = document.createElement("div");
            el.innerText = emojis[Math.floor(Math.random() * emojis.length)];
            el.classList.add("floating-emoji");
            el.style.left = Math.random() * 100 + "vw";
            const duration = Math.random() * 5 + 5;
            el.style.animationDuration = duration + "s";
            el.style.fontSize = Math.random() * 20 + 20 + "px";
            if (isInstant) {
                el.style.animationDelay = -(Math.random() * duration) + "s";
            }
            document.body.appendChild(el);
            setTimeout(() => el.remove(), duration * 1000);
        }

        function startFloatingHearts() {
            setInterval(() => createFloatingEmoji(false), 600);
            for (let i = 0; i < 15; i++) createFloatingEmoji(true);
        }
    </script>
  </body>
</html>