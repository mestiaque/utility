<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>আমার ভালোবাসা তোমার জন্য</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #ffe6e6, #ffb3b3);
            font-family: 'Arial', sans-serif;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            position: relative;
        }

        /* ব্যাকগ্রাউন্ড হার্ট অ্যানিমেশন */
        .heart-particle {
            position: absolute;
            color: #ff4d4d;
            font-size: 20px;
            animation: fall linear infinite;
            z-index: 1;
            user-select: none;
        }

        @keyframes fall {
            0% {
                transform: translateY(-10vh) scale(0);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            100% {
                transform: translateY(110vh) scale(1.2);
                opacity: 0;
            }
        }

        /* মেইন কন্টেইনার */
        .container {
            background: rgba(255, 255, 255, 0.85);
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 90%;
            width: 400px;
            z-index: 10;
            backdrop-filter: blur(5px);
            border: 2px solid #ff9999;
            transform: scale(0.9);
            animation: popIn 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
        }

        @keyframes popIn {
            to { transform: scale(1); }
        }

        /* বাটন ও এলিমেন্ট */
        .main-heart {
            font-size: 70px;
            cursor: pointer;
            display: inline-block;
            animation: beat 1.2s infinite;
            transition: transform 0.3s;
        }

        @keyframes beat {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.2); }
        }

        .instruction {
            color: #666;
            margin-top: 15px;
            font-size: 14px;
        }

        /* হিডেন মেসেজ এরিয়া */
        .message-box {
            display: none;
            margin-top: 20px;
            animation: fadeIn 1s ease-in-out forwards;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .love-title {
            color: #d63031;
            font-size: 28px;
            margin-bottom: 15px;
            font-weight: bold;
        }

        .love-text {
            color: #4a4a4a;
            font-size: 18px;
            line-height: 1.6;
        }
    </style>
</head>
<body>

    <div class="container">
        <!-- প্রথমাবস্থায় এই বড় হার্টটি দেখাবে -->
        <div class="main-heart" id="clickableHeart" onclick="revealMessage()">❤️</div>
        <p class="instruction" id="tapText">এখানে টাচ করো Jan</p>

        <!-- টাচ করার পর এই মেসেজটি দেখাবে -->
        <div class="message-box" id="loveMessage">
            <!-- এখানে আপনার স্ত্রীর নাম দিতে পারেন -->
            <h1 class="love-title">I Love You, My Queen! 💖</h1>
            <p class="love-text">
                তুমি আমার জীবনের সবচেয়ে সুন্দর উপহার। পৃথিবীর সবকিছুর চেয়ে আমি তোমাকে বেশি ভালোবাসি এবং সারাজীবন তোমার পাশে থাকতে চাই। 🥰
            </p>
        </div>
    </div>

    <script>
        // ব্যাকগ্রাউন্ডে অনবরত হার্ট পড়ার স্ক্রিপ্ট
        function createHeart() {
            const heart = document.createElement('div');
            heart.classList.add('heart-particle');
            heart.innerHTML = '❤️';
            
            // র্যান্ডম পজিশন ও সাইজ
            heart.style.left = Math.random() * 100 + 'vw';
            heart.style.animationDuration = Math.random() * 3 + 2 + 's'; // ২ থেকে ৫ সেকেন্ড
            heart.style.opacity = Math.random();
            
            document.body.appendChild(heart);
            
            // স্ক্রিন থেকে ডিলিট করা যেন ব্রাউজার স্লো না হয়
            setTimeout(() => {
                heart.remove();
            }, 5000);
        }

        // প্রতি ৩০০ মিলিসেকেন্ডে একটি করে হার্ট তৈরি হবে
        setInterval(createHeart, 300);

        // বাটনে ক্লিক করলে মেসেজ দেখানোর ফাংশন
        function revealMessage() {
            document.getElementById('loveMessage').style.display = 'block';
            document.getElementById('tapText').style.display = 'none';
            
            // ক্লিক করার পর হার্টের অ্যানিমেশন একটু স্পিডি করা
            const heartEl = document.getElementById('clickableHeart');
            heartEl.style.animation = 'beat 0.5s infinite';
            
            // একবারে অনেকগুলো এক্সট্রা হার্ট তৈরি করা ব্লাস্ট ইফেক্টের জন্য
            for(let i=0; i<15; i++) {
                setTimeout(createHeart, i * 50);
            }
        }
    </script>
</body>
</html>
