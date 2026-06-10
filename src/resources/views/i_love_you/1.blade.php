<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>আমার অর্ধাঙ্গিনীর জন্য</title>
    <meta name="title" content="আমার অর্ধাঙ্গিনীর জন্য">
    <meta name="description" content="ভালোবাসার একটি সুন্দর সারপ্রাইজ পেজ - হৃদয়ের বার্তা, ছবি, অ্যানিমেশন ও মিউজিকসহ।">
    <link rel="icon" href="{{ get_image('app_ico') ?? asset('assets/img/favicon/Encodex.ico') }}" type="image/x-icon">
    <link rel="apple-touch-icon" href="{{ get_image('app_ico') ?? asset('assets/img/favicon/Encodex.ico') }}">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@500;700&family=Hind+Siliguri:wght@400;500;600;700&display=swap');

        :root {
            --rose: #ff4f8b;
            --coral: #ff8c8c;
            --gold: #f6ce6c;
            --ink: #0e0717;
            --violet: #2e173f;
            --glass: rgba(17, 10, 30, 0.68);
            --line: rgba(246, 206, 108, 0.38);
            --text-soft: #e9ddf0;
            --text-muted: #bea8cf;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background:
                radial-gradient(circle at 16% 20%, #4a1f4f 0%, rgba(74, 31, 79, 0) 40%),
                radial-gradient(circle at 80% 10%, #4a2047 0%, rgba(74, 32, 71, 0) 34%),
                radial-gradient(circle at 50% 90%, #1d0f2f 0%, rgba(29, 15, 47, 0) 36%),
                linear-gradient(145deg, var(--ink), #150b24 55%, #0f081b);
            font-family: 'Hind Siliguri', sans-serif;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            position: relative;
            perspective: 1200px;
            color: var(--text-soft);
        }

        body::before {
            content: '';
            position: absolute;
            inset: 0;
            z-index: 0;
            opacity: 0.22;
            background-image: radial-gradient(circle, rgba(255, 255, 255, 0.2) 0.5px, transparent 0.8px);
            background-size: 3px 3px;
            pointer-events: none;
        }

        .ambient-glow {
            position: absolute;
            width: 520px;
            height: 520px;
            border-radius: 50%;
            filter: blur(55px);
            opacity: 0.32;
            z-index: 0;
            pointer-events: none;
            animation: drift 12s ease-in-out infinite alternate;
        }

        .ambient-a {
            top: -220px;
            right: -140px;
            background: #ff5c88;
        }

        .ambient-b {
            bottom: -250px;
            left: -180px;
            background: #ffd37b;
            animation-duration: 14s;
        }

        @keyframes drift {
            from { transform: translateY(0) translateX(0) scale(1); }
            to { transform: translateY(40px) translateX(-20px) scale(1.07); }
        }

        canvas {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            pointer-events: none;
        }

        .card {
            position: relative;
            background: var(--glass);
            border: 1.6px solid rgba(255, 255, 255, 0.12);
            border-radius: 28px;
            padding: 44px 30px 30px;
            width: 90%;
            max-width: 470px;
            text-align: center;
            z-index: 10;
            backdrop-filter: blur(14px);
            box-shadow: 0 26px 70px rgba(0, 0, 0, 0.5), 0 0 70px rgba(255, 79, 139, 0.17);
            transform: rotateX(9deg);
            transition: transform 0.55s cubic-bezier(0.2, 0.9, 0.2, 1), box-shadow 0.55s ease;
            overflow: hidden;
        }

        .card::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: inherit;
            padding: 1.2px;
            background: linear-gradient(145deg, rgba(246, 206, 108, 0.45), rgba(255, 79, 139, 0.25), rgba(255, 255, 255, 0.12));
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            pointer-events: none;
        }

        .date-badge {
            position: absolute;
            top: 14px;
            right: 16px;
            border: 1px solid var(--line);
            border-radius: 999px;
            padding: 5px 12px;
            font-size: 12px;
            color: #ffe8b7;
            letter-spacing: 0.4px;
            background: rgba(246, 206, 108, 0.08);
            z-index: 2;
        }

        .card:hover {
            transform: rotateX(0deg) translateY(-6px);
            box-shadow: 0 30px 75px rgba(0, 0, 0, 0.6), 0 0 86px rgba(255, 79, 139, 0.28);
        }

        .profile-container {
            width: 148px;
            height: 148px;
            margin: 5px auto 20px;
            position: relative;
            display: none;
            opacity: 0;
            transform: translateY(8px) scale(0.88);
        }

        .profile-container.visible {
            animation: revealProfile 0.85s cubic-bezier(0.2, 0.85, 0.2, 1) forwards;
        }

        .profile-pic {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--gold);
            box-shadow: 0 0 24px rgba(255, 79, 139, 0.38);
            position: relative;
            z-index: 3;
        }

        .ring,
        .pulse-ring {
            position: absolute;
            inset: -8px;
            border-radius: 50%;
            pointer-events: none;
        }

        .ring {
            border: 1.4px solid rgba(246, 206, 108, 0.65);
            animation: rotateRing 8s linear infinite;
        }

        .pulse-ring {
            border: 1.4px solid rgba(255, 79, 139, 0.6);
            inset: -14px;
            animation: pulseRing 2.3s ease-out infinite;
        }

        .neon-heart-wrap {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
        }

        .neon-heart {
            font-size: 84px;
            cursor: pointer;
            filter: drop-shadow(0 0 16px var(--rose));
            display: inline-block;
            animation: heartbeat 1.08s infinite alternate cubic-bezier(0.25, 0.65, 0.35, 1);
            user-select: none;
            transition: transform 0.25s ease;
        }

        .neon-heart:active {
            transform: scale(0.86);
        }

        @keyframes heartbeat {
            0% { transform: scale(1); filter: drop-shadow(0 0 10px var(--rose)); }
            100% { transform: scale(1.2); filter: drop-shadow(0 0 28px var(--rose)) drop-shadow(0 0 40px var(--coral)); }
        }

        .hint-text {
            color: var(--text-muted);
            font-size: 15px;
            letter-spacing: 0.7px;
            animation: pulseText 1.6s infinite;
        }

        @keyframes pulseText {
            0%, 100% { opacity: 0.6; }
            50% { opacity: 1; color: #ffb4c7; }
        }

        .content-box {
            display: none;
            opacity: 0;
            transform: translateY(8px);
        }

        .content-box.visible {
            display: block;
            animation: revealContent 0.75s ease forwards;
        }

        .headline {
            font-family: 'Cinzel', serif;
            background: linear-gradient(60deg, #ffe7ac, #fff8eb, #ff9cb3);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 33px;
            font-weight: 700;
            margin-bottom: 10px;
            min-height: 48px;
            letter-spacing: 0.6px;
            text-shadow: 0 0 24px rgba(246, 206, 108, 0.2);
            opacity: 0;
            transform: translateY(8px);
        }

        .headline.visible {
            animation: revealText 0.65s ease forwards;
        }

        .love-letter {
            color: var(--text-soft);
            font-size: 17px;
            line-height: 1.85;
            text-align: center;
            min-height: 102px;
            opacity: 0;
            transform: translateY(8px);
        }

        .love-letter.visible {
            animation: revealText 0.65s ease forwards;
        }

        .typing-cursor {
            display: inline-block;
            width: 10px;
            color: #ffd8e3;
            animation: blink 0.8s steps(1, end) infinite;
        }

        .promise-box {
            margin-top: 18px;
            padding: 10px 14px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 12px;
            font-size: 14px;
            color: #f9edf8;
            background: rgba(255, 255, 255, 0.05);
            opacity: 0;
            transform: translateY(8px);
            transition: opacity 0.4s ease, transform 0.4s ease;
        }

        .promise-box.show {
            opacity: 1;
            transform: translateY(0);
        }

        .signature {
            margin-top: 14px;
            font-family: 'Cinzel', serif;
            color: #f7dfa4;
            letter-spacing: 0.7px;
            font-size: 14px;
            opacity: 0;
            transform: translateY(8px);
            transition: opacity 0.45s ease, transform 0.45s ease;
        }

        .signature.show {
            opacity: 1;
            transform: translateY(0);
        }

        .toast {
            position: fixed;
            bottom: 22px;
            left: 50%;
            transform: translateX(-50%) translateY(20px);
            padding: 10px 16px;
            border-radius: 10px;
            font-size: 14px;
            color: #fff;
            background: rgba(12, 7, 20, 0.86);
            border: 1px solid rgba(255, 255, 255, 0.2);
            z-index: 20;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.28s ease, transform 0.28s ease;
        }

        .toast.show {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }

        @keyframes revealProfile {
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @keyframes revealContent {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes revealText {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes rotateRing {
            to { transform: rotate(360deg); }
        }

        @keyframes pulseRing {
            0% { transform: scale(0.96); opacity: 0.55; }
            70% { transform: scale(1.08); opacity: 0; }
            100% { transform: scale(1.08); opacity: 0; }
        }

        @keyframes blink {
            0%, 49% { opacity: 1; }
            50%, 100% { opacity: 0; }
        }

        @media (max-width: 560px) {
            .card {
                padding: 40px 20px 24px;
            }

            .headline {
                font-size: 26px;
                min-height: 40px;
            }

            .love-letter {
                font-size: 15px;
                min-height: 112px;
            }

            .profile-container {
                width: 130px;
                height: 130px;
            }

            .neon-heart {
                font-size: 72px;
            }
        }
    </style>
</head>
<body>
    <div class="ambient-glow ambient-a"></div>
    <div class="ambient-glow ambient-b"></div>

    <canvas id="heartCanvas"></canvas>

    <audio id="bgMusic" loop>
        <source src="https://www.soundhelix.com/examples/mp3/SoundHelix-Song-1.mp3" type="audio/mp3">
    </audio>

    {{-- <div class="toast" id="toast">Music play করতে ট্যাপ করে browser permission allow করো</div> --}}

    <div class="card">
        <span class="date-badge" id="dateBadge" style="display: none;"></span>

        <div id="initSection">
            <div class="neon-heart-wrap">
                <div class="neon-heart" id="heartBtn" onclick="unlockLove()">💝</div>
                <p class="hint-text">হৃদয়টা স্পর্শ করো, জান...</p>
            </div>
        </div>

        <div id="surpriseSection" class="content-box">
            <div class="profile-container" id="profileBox">
                <img src="{{ asset('utility/jan1.png') }}" alt="Wife" class="profile-pic">
                <span class="ring"></span>
                <span class="pulse-ring"></span>
            </div>
            <h1 class="headline" id="titleText"></h1>
            <p class="love-letter" id="typedLetter"></p>
            <p class="promise-box" id="promiseBox">যত ঝড়ই আসুক, আমরা একসাথে হাত ধরে থাকব।</p>
            <p class="signature" id="signature">Forever Yours</p>
        </div>
    </div>

    <script>
        const canvas = document.getElementById('heartCanvas');
        const ctx = canvas.getContext('2d');
        let particles = [];
        let unlocked = false;

        const titleToType = 'I Love You, Jaan!';
        const textToType = 'তুমি আমার জীবনের সবচেয়ে সুন্দর অধ্যায়। তোমার সাথে প্রতিটা দিন যেন নতুন ভোরের মতো। আই লাভ ইউ সো মাচ, জানু।';

        const titleEl = document.getElementById('titleText');
        const letterEl = document.getElementById('typedLetter');
        const promiseBox = document.getElementById('promiseBox');
        const signature = document.getElementById('signature');

        const cursor = document.createElement('span');
        cursor.className = 'typing-cursor';
        cursor.textContent = '|';
        letterEl.appendChild(cursor);

        function setDateBadge() {
            const badge = document.getElementById('dateBadge');
            const today = new Date();
            badge.textContent = today.toLocaleDateString('bn-BD', { day: 'numeric', month: 'long', year: 'numeric' });
        }
        setDateBadge();

        function resizeCanvas() {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        }
        window.addEventListener('resize', resizeCanvas);
        resizeCanvas();

        class Particle {
            constructor(x, y, isBlast = false) {
                this.x = x;
                this.y = y;
                this.isBlast = isBlast;
                this.size = Math.random() * (isBlast ? 8 : 4) + 2;
                this.speedX = isBlast ? (Math.random() - 0.5) * 12 : (Math.random() - 0.5) * 1.5;
                this.speedY = isBlast ? (Math.random() - 0.5) * 12 : -Math.random() * 2 - 1;
                this.opacity = 1;
                this.color = `hsl(${Math.random() * 30 + 340}, 100%, ${Math.random() * 20 + 50}%)`;
                this.fadeRate = Math.random() * 0.015 + 0.005;
            }
            update() {
                this.x += this.speedX;
                this.y += this.speedY;
                if (this.isBlast) {
                    this.speedY += 0.1;
                }
                this.opacity -= this.fadeRate;
            }
            draw() {
                ctx.save();
                ctx.globalAlpha = this.opacity;
                ctx.fillStyle = this.color;
                ctx.shadowBlur = 10;
                ctx.shadowColor = this.color;
                
                ctx.beginPath();
                ctx.moveTo(this.x, this.y);
                ctx.bezierCurveTo(this.x - this.size/2, this.y - this.size/2, this.x - this.size, this.y + this.size/3, this.x, this.y + this.size * 1.2);
                ctx.bezierCurveTo(this.x + this.size, this.y + this.size/3, this.x + this.size/2, this.y - this.size/2, this.x, this.y);
                ctx.fill();
                ctx.restore();
            }
        }

        function addFloatingHearts() {
            if (particles.length < 180) {
                particles.push(new Particle(Math.random() * canvas.width, canvas.height + 20));
            }
        }

        function animate() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            addFloatingHearts();
            
            for (let i = particles.length - 1; i >= 0; i--) {
                particles[i].update();
                particles[i].draw();
                if (particles[i].opacity <= 0) {
                    particles.splice(i, 1);
                }
            }
            requestAnimationFrame(animate);
        }
        animate();

        function typeWriterTitle(index = 0) {
            if (!titleEl.classList.contains('visible')) {
                titleEl.classList.add('visible');
            }

            if (titleIndex < titleToType.length) {
                titleEl.textContent += titleToType.charAt(titleIndex);
                titleIndex += 1;
                setTimeout(() => typeWriterTitle(index + 1), 65);
            } else {
                setTimeout(() => {
                    letterEl.classList.add('visible');
                    typeWriterLetter(0);
                }, 150);
            }
        }

        let titleIndex = 0;

        function typeWriterLetter(index) {
            if (index < textToType.length) {
                cursor.insertAdjacentText('beforebegin', textToType.charAt(index));
                setTimeout(() => typeWriterLetter(index + 1), 46);
                return;
            }

            cursor.remove();
            promiseBox.classList.add('show');
            setTimeout(() => signature.classList.add('show'), 180);
        }

        function showToast(text) {
            const toast = document.getElementById('toast');
            toast.textContent = text;
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 2600);
        }

        function fadeInMusic(audio, targetVolume = 0.4, duration = 1800) {
            audio.volume = 0;
            const stepMs = 100;
            const increment = targetVolume / (duration / stepMs);
            const timer = setInterval(() => {
                audio.volume = Math.min(targetVolume, audio.volume + increment);
                if (audio.volume >= targetVolume) {
                    clearInterval(timer);
                }
            }, stepMs);
        }

        function unlockLove() {
            if (unlocked) return;
            unlocked = true;

            const music = document.getElementById('bgMusic');
            music.play().then(() => {
                fadeInMusic(music, 0.42, 2000);
            }).catch(() => {
                showToast('Music চালু করতে browser এ interaction permission দরকার');
            });

            const rect = document.getElementById('heartBtn').getBoundingClientRect();
            const blastX = rect.left + rect.width / 2;
            const blastY = rect.top + rect.height / 2;

            for (let i = 0; i < 95; i++) {
                particles.push(new Particle(blastX, blastY, true));
            }

            document.getElementById('initSection').style.display = 'none';
            const surpriseSection = document.getElementById('surpriseSection');
            const profileBox = document.getElementById('profileBox');

            surpriseSection.style.display = 'block';
            requestAnimationFrame(() => surpriseSection.classList.add('visible'));

            setTimeout(() => {
                profileBox.style.display = 'block';
                requestAnimationFrame(() => profileBox.classList.add('visible'));
            }, 240);

            setTimeout(() => {
                typeWriterTitle();
            }, 700);
        }
    </script>

</body>
</html>