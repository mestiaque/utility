<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>আমার অর্ধাঙ্গিনীর জন্য...</title>
    <style>
        :root {
            --primary: #ff2a74;
            --secondary: #ff758c;
            --gold: #ffd700;
            --dark: #0a0512;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: var(--dark);
            background-image: radial-gradient(circle at center, #200b26 0%, #0a0512 100%);
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            position: relative;
            perspective: 1000px;
        }

        /* ক্যানভাস (অ্যানিমেশনের জন্য) */
        canvas {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            pointer-events: none;
        }

        /* প্রিমিয়াম গ্লাস-মরফিজম কার্ড */
        .card {
            background: rgba(15, 8, 25, 0.7);
            border: 2px solid rgba(255, 42, 116, 0.3);
            border-radius: 24px;
            padding: 40px 30px;
            width: 90%;
            max-width: 440px;
            text-align: center;
            z-index: 10;
            backdrop-filter: blur(15px);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5),
                        0 0 40px rgba(255, 42, 116, 0.1);
            transform: rotateX(10deg);
            transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .card:hover {
            transform: rotateX(0deg) translateY(-5px);
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.6),
                        0 0 50px rgba(255, 42, 116, 0.25);
            border-color: rgba(255, 42, 116, 0.6);
        }

        /* গ্লোয়িং প্রোফাইল পিকচার */
        .profile-container {
            width: 130px;
            height: 130px;
            margin: 0 auto 25px;
            position: relative;
            display: none; /* শুরুতে লুকানো থাকবে, ক্লিকের পর আসবে */
            animation: scaleIn 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
        }

        .profile-pic {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--gold);
            box-shadow: 0 0 20px var(--primary);
        }

        /* ইন্টারেক্টিভ নিয়ন হার্ট বাটন */
        .neon-heart {
            font-size: 80px;
            cursor: pointer;
            filter: drop-shadow(0 0 15px var(--primary));
            display: inline-block;
            animation: heartbeat 1s infinite alternate cubic-bezier(0.215, 0.610, 0.355, 1);
            user-select: none;
            transition: transform 0.2s;
        }

        .neon-heart:active {
            transform: scale(0.8);
        }

        @keyframes heartbeat {
            0% { transform: scale(1); filter: drop-shadow(0 0 10px var(--primary)); }
            100% { transform: scale(1.2); filter: drop-shadow(0 0 25px var(--primary)) drop-shadow(0 0 40px var(--secondary)); }
        }

        .hint-text {
            color: #b3a1bf;
            font-size: 15px;
            margin-top: 20px;
            letter-spacing: 1px;
            animation: pulseText 1.5s infinite;
        }

        @keyframes pulseText {
            0%, 100% { opacity: 0.6; }
            50% { opacity: 1; color: var(--secondary); }
        }

        /* সারপ্রাইজ মেসেজ বক্স */
        .content-box {
            display: none;
        }

        .headline {
            background: linear-gradient(45deg, var(--gold), #fff, var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 30px;
            font-weight: 800;
            margin-bottom: 15px;
            text-shadow: 0 0 30px rgba(255,217,0,0.2);
        }

        .love-letter {
            color: #f0e6f5;
            font-size: 17px;
            line-height: 1.8;
            text-align: center;
            min-height: 80px;
        }

        @keyframes scaleIn {
            from { transform: scale(0); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
    </style>
</head>
<body>

    <!-- ব্যাকগ্রাউন্ড কণা অ্যানিমেশনের জন্য ক্যানভাস -->
    <canvas id="heartCanvas"></canvas>

    <!-- ব্যাকগ্রাউন্ড মিউজিক (ক্লিকের পর প্লে হবে অটোমেটিক) -->
    <audio id="bgMusic" loop>
        <source src="https://soundhelix.com" type="audio/mp3">
    </audio>

    <div class="card">
        <!-- শুরুতে এটি দেখাবে -->
        <div id="initSection">
            <div class="neon-heart" onclick="unlockLove()">💝</div>
            <p class="hint-text">এখানে স্পর্শ করো, মাই কুইন...</p>
        </div>

        <!-- ক্লিকের পর ম্যাজিক কন্টেন্ট -->
        <div id="surpriseSection" class="content-box">
            <div class="profile-container" id="profileBox">
                <!-- আপনার স্ত্রীর ছবির লিঙ্ক এখানে দিন (অথবা একই ফোল্ডারে ছবি রেখে নাম দিন যেমন: wife.jpg) -->
                <img src="{{ asset('utility/jan1.png') }}" alt="Wife" class="profile-pic">

            </div>
            <h1 class="headline" id="titleText"></h1>
            <p class="love-letter" id="typedLetter"></p>
        </div>
    </div>

    <script>
        // ১. ক্যানভাস সেটাপ ও পার্টিকেল ইঞ্জিন (High-Performance 2D Engine)
        const canvas = document.getElementById('heartCanvas');
        const ctx = canvas.getContext('2d');
        let particles = [];

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
                    this.speedY += 0.1; // গ্র্যাভিটি ইফেক্ট
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
            if(particles.length < 150) {
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

        // ২. টাইপরাইটার কনফিগ (গ্লোবাল স্কোপ)
        const titleToType = "I Love You, Jaan! 🌟";
        const textToType = "তুমি আমার জীবনের সবচেয়ে সুন্দর এবং সেরা অধ্যায়। আমাদের নতুন এই পথচলা যেন আজীবন এমন ভালোবাসায় মোড়ানো থাকে। আই লাভ ইউ সো মাচ, জানু! 💍💖";
        
        let titleIndex = 0;
        let letterIndex = 0;

        function typeWriterEffect() {
            const titleEl = document.getElementById('titleText');
            const letterEl = document.getElementById('typedLetter');

            // প্রথমে টাইটেল টাইপ হবে
            if (titleIndex < titleToType.length) {
                titleEl.innerHTML += titleToType.charAt(titleIndex);
                titleIndex++;
                setTimeout(typeWriterEffect, 60);
            } 
            // টাইটেল শেষ হলে মূল চিঠি শুরু হবে
            else if (letterIndex < textToType.length) {
                letterEl.innerHTML += textToType.charAt(letterIndex);
                letterIndex++;
                setTimeout(typeWriterEffect, 50);
            }
        }

        // ৩. ক্লিকের পর মেইন একশন ট্রিগার
        function unlockLove() {
            // মিউজিক প্লে
            const music = document.getElementById('bgMusic');
            music.play().catch(e => console.log("Audio interaction restriction handled."));

            // হার্ট বিস্ফোরণ
            const rect = document.querySelector('.neon-heart').getBoundingClientRect();
            const blastX = rect.left + rect.width / 2;
            const blastY = rect.top + rect.height / 2;
            
            for(let i=0; i<80; i++) {
                particles.push(new Particle(blastX, blastY, true));
            }

            // UI চেঞ্জ
            document.getElementById('initSection').style.display = 'none';
            document.getElementById('surpriseSection').style.display = 'block';
            
            setTimeout(() => {
                document.getElementById('profileBox').style.display = 'block';
                typeWriterEffect(); // টাইপরাইটার শুরু
            }, 400);
        }
    </script>

</body>
</html>