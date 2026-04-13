@extends('me::master')

@section('title', 'Wedding Card Generator')

@section('content')
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body, html {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        .wedding-card-wrapper {
            font-family: 'SolaimanLipi', 'Kalpurush', Arial, sans-serif;
            /* background: linear-gradient(135deg, #c41e3a 0%, #880e4f 50%, #d81b60 100%); */
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
            overflow-y: auto;
        }

        .wedding-card-wrapper::before {
            content: '🌸';
            position: absolute;
            top: 20px;
            left: 20px;
            font-size: 60px;
            opacity: 0.1;
            animation: float 6s ease-in-out infinite;
        }

        .wedding-card-wrapper::after {
            content: '🪷';
            position: absolute;
            bottom: 20px;
            right: 20px;
            font-size: 60px;
            opacity: 0.1;
            animation: float 8s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        .controls {
            background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
            padding: 18px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            margin-bottom: 25px;
            max-width: 900px;
            width: 100%;
            border: 2px solid #c41e3a;
            position: relative;
            overflow: hidden;
            margin-left: auto;
            margin-right: auto;
        }

        .controls::before {
            content: '🌸';
            position: absolute;
            top: 0;
            left: 0;
            font-size: 50px;
            opacity: 0.08;
        }

        .controls::after {
            content: '🪷';
            position: absolute;
            bottom: 0;
            right: 0;
            font-size: 50px;
            opacity: 0.08;
        }

        .controls h3 {
            color: #c41e3a;
            margin-bottom: 15px;
            text-align: center;
            font-size: 20px;
            text-shadow: 1px 1px 2px rgba(196, 30, 58, 0.2);
            position: relative;
            z-index: 1;
        }

        .input-group {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
            margin-bottom: 15px;
            width: 100%;
            box-sizing: border-box;
        }

        .input-field {
            display: flex;
            flex-direction: column;
            width: 100%;
            box-sizing: border-box;
        }

        .input-field label {
            color: #1a1a1a;
            margin-bottom: 4px;
            font-weight: bold;
            font-size: 13px;
        }

        .input-field input {
            padding: 8px;
            border: 2px solid #c41e3a;
            border-radius: 8px;
            font-size: 13px;
            transition: all 0.3s;
            background: linear-gradient(135deg, #fff 0%, #fffef8 100%);
            width: 100%;
            box-sizing: border-box;
        }

        .input-field input:focus {
            outline: none;
            border-color: #ff1744;
            box-shadow: 0 0 10px rgba(196, 30, 58, 0.3);
            transform: translateY(-2px);
        }

        .download-btn {
            width: 100%;
            max-width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #c41e3a 0%, #880e4f 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(196, 30, 58, 0.4);
            position: relative;
            z-index: 1;
            box-sizing: border-box;
        }

        .download-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(196, 30, 58, 0.6);
            background: linear-gradient(135deg, #d81b60 0%, #ad1457 100%);
        }

        .reset-btn {
            width: 100%;
            max-width: 100%;
            padding: 10px;
            background: linear-gradient(135deg, #757575 0%, #424242 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 10px;
            box-sizing: border-box;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            position: relative;
            z-index: 1;
        }

        .reset-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.5);
            background: linear-gradient(135deg, #616161 0%, #212121 100%);
        }

        .card-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            max-width: 900px;
            width: 100%;
            margin: 0 auto;
        }

        .wedding-card {
            background: linear-gradient(135deg, #fff9e6 0%, #ffe8cc 50%, #ffd4a3 100%);
            padding: 25px;
            position: relative;
            overflow: hidden;
            width: 100%;
            box-sizing: border-box;
        }

        .wedding-card::before {
            content: '';
            position: absolute;
            top: -10px;
            left: -10px;
            width: 120px;
            height: 120px;
            background: radial-gradient(circle, rgba(255, 200, 124, 0.3) 0%, transparent 70%);
            border-radius: 50%;
        }

        .wedding-card::after {
            content: '';
            position: absolute;
            bottom: -10px;
            right: -10px;
            width: 120px;
            height: 120px;
            background: radial-gradient(circle, rgba(255, 180, 100, 0.3) 0%, transparent 70%);
            border-radius: 50%;
        }

        .decorative-border {
            border: 3px double #c41e3a;
            border-radius: 15px;
            padding: 20px;
            background: linear-gradient(to bottom, #ffffff 0%, #fffef8 100%);
            box-shadow: inset 0 0 30px rgba(196, 30, 58, 0.08),
                        0 8px 32px rgba(0, 0, 0, 0.12);
            position: relative;
            width: 100%;
            box-sizing: border-box;
        }

        .decorative-border::before,
        .decorative-border::after {
            content: '🌿';
            position: absolute;
            font-size: 25px;
            opacity: 0.6;
        }

        .decorative-border::before {
            top: 8px;
            left: 8px;
            transform: rotate(-15deg);
        }

        .decorative-border::after {
            bottom: 8px;
            right: 8px;
            transform: rotate(15deg);
        }

        /* Bengali traditional corner decorations */
        .corner-decoration {
            position: absolute;
            width: 50px;
            height: 50px;
            pointer-events: none;
            z-index: 2;
        }

        .corner-top-left {
            top: 0;
            left: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><path d="M0,0 Q30,0 30,30 L0,30 Z M10,40 Q15,35 20,40 L15,45 Q10,40 10,40 M25,25 Q35,25 35,35 L25,35 Z" fill="%23c41e3a" opacity="0.7"/><circle cx="15" cy="15" r="3" fill="%23ffd700"/><circle cx="40" cy="15" r="2" fill="%23ff6b6b"/></svg>') no-repeat;
            background-size: contain;
        }

        .corner-top-right {
            top: 0;
            right: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><path d="M100,0 Q70,0 70,30 L100,30 Z M90,40 Q85,35 80,40 L85,45 Q90,40 90,40 M75,25 Q65,25 65,35 L75,35 Z" fill="%23c41e3a" opacity="0.7"/><circle cx="85" cy="15" r="3" fill="%23ffd700"/><circle cx="60" cy="15" r="2" fill="%23ff6b6b"/></svg>') no-repeat;
            background-size: contain;
        }

        .corner-bottom-left {
            bottom: 0;
            left: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><path d="M0,100 Q30,100 30,70 L0,70 Z M10,60 Q15,65 20,60 L15,55 Q10,60 10,60 M25,75 Q35,75 35,65 L25,65 Z" fill="%23c41e3a" opacity="0.7"/><circle cx="15" cy="85" r="3" fill="%23ffd700"/><circle cx="40" cy="85" r="2" fill="%23ff6b6b"/></svg>') no-repeat;
            background-size: contain;
        }

        .corner-bottom-right {
            bottom: 0;
            right: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><path d="M100,100 Q70,100 70,70 L100,70 Z M90,60 Q85,65 80,60 L85,55 Q90,60 90,60 M75,75 Q65,75 65,65 L75,65 Z" fill="%23c41e3a" opacity="0.7"/><circle cx="85" cy="85" r="3" fill="%23ffd700"/><circle cx="60" cy="85" r="2" fill="%23ff6b6b"/></svg>') no-repeat;
            background-size: contain;
        }

        .header {
            text-align: center;
            margin-bottom: 12px;
            width: 100%;
            box-sizing: border-box;
        }

        .bismillah {
            font-size: 22px;
            color: #1e5d2f;
            margin-bottom: 10px;
            font-weight: bold;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1);
            padding: 8px;
            background: linear-gradient(135deg, rgba(255, 239, 213, 0.8), rgba(255, 228, 196, 0.8));
            border-radius: 8px;
            border: 2px solid rgba(196, 30, 58, 0.2);
            width: 100%;
            box-sizing: border-box;
        }

        .title {
            font-size: 30px;
            color: #c41e3a;
            font-weight: bold;
            margin-bottom: 8px;
            text-shadow: 2px 2px 4px rgba(196, 30, 58, 0.3);
            position: relative;
            padding: 5px 0;
            width: 100%;
            box-sizing: border-box;
        }

        .title::before,
        .title::after {
            content: '🪷';
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            font-size: 22px;
            opacity: 0.8;
            display: none; /* Hidden for compact layout */
        }

        .title::before {
            left: -30px;
        }

        .title::after {
            right: -30px;
        }

        .subtitle {
            font-size: 14px;
            color: #555;
            font-style: italic;
            line-height: 1.4;
            padding: 6px 15px;
            background: linear-gradient(to right, transparent, rgba(196, 30, 58, 0.05), transparent);
            border-radius: 6px;
            width: 100%;
            box-sizing: border-box;
        }

        .couple-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin: 15px 0;
            position: relative;
            width: 100%;
            box-sizing: border-box;
        }

        .couple-info::before {
            content: '❤️';
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            font-size: 20px;
            z-index: 1;
            /* background: white; */
            padding: 8px;
            border-radius: 50%;
            /* box-shadow: 0 4px 20px rgba(196, 30, 58, 0.3); */
            /* border: 2px solid #c41e3a; */
        }

        /* Decorative vines */
        .vine-left, .vine-right {
            position: absolute;
            top: 0;
            bottom: 0;
            width: 40px;
            background-repeat: repeat-y;
            background-size: contain;
            opacity: 0.3;
            pointer-events: none;
            display: none; /* Hidden for compact layout */
        }

        .vine-left {
            left: 0;
            background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 60 120"><path d="M30,0 Q20,20 30,40 Q40,60 30,80 Q20,100 30,120" stroke="%231e5d2f" stroke-width="3" fill="none"/><circle cx="25" cy="20" r="8" fill="%2300a86b"/><circle cx="35" cy="60" r="6" fill="%2300c781"/><circle cx="25" cy="100" r="7" fill="%2300a86b"/><path d="M20,30 Q15,25 10,30 L15,35 Z" fill="%231e5d2f"/><path d="M40,70 Q45,65 50,70 L45,75 Z" fill="%231e5d2f"/></svg>');
        }

        .vine-right {
            right: 0;
            background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 60 120"><path d="M30,0 Q40,20 30,40 Q20,60 30,80 Q40,100 30,120" stroke="%231e5d2f" stroke-width="3" fill="none"/><circle cx="35" cy="20" r="8" fill="%2300a86b"/><circle cx="25" cy="60" r="6" fill="%2300c781"/><circle cx="35" cy="100" r="7" fill="%2300a86b"/><path d="M40,30 Q45,25 50,30 L45,35 Z" fill="%231e5d2f"/><path d="M20,70 Q15,65 10,70 L15,75 Z" fill="%231e5d2f"/></svg>');
        }

        .groom-info, .bride-info {
            background: linear-gradient(135deg, #fff4e6 0%, #ffe0b2 50%, #ffcc80 100%);
            padding: 15px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
            border: 2px solid #ff6f00;
            position: relative;
            overflow: hidden;
            width: 100%;
            box-sizing: border-box;
        }

        .groom-info::before {
            content: '🍃';
            position: absolute;
            top: 0;
            right: 0;
            font-size: 40px;
            opacity: 0.2;
            transform: rotate(20deg);
        }

        .bride-info {
            background: linear-gradient(135deg, #ffe5e8 0%, #ffcdd2 50%, #e57373 100%);
            border: 2px solid #c41e3a;
        }

        .bride-info::before {
            content: '🌸';
            position: absolute;
            top: 0;
            left: 0;
            font-size: 40px;
            opacity: 0.2;
            transform: rotate(-20deg);
        }

        .person-title {
            font-size: 18px;
            font-weight: bold;
            color: #1a1a1a;
            text-align: center;
            margin-bottom: 10px;
            padding: 8px 8px;
            border-bottom: 2px double rgba(0, 0, 0, 0.3);
            background: rgba(255, 255, 255, 0.5);
            border-radius: 8px 8px 0 0;
            text-shadow: 1px 1px 2px rgba(255, 255, 255, 0.8);
            width: 100%;
            box-sizing: border-box;
            word-wrap: break-word;
        }

        .info-item {
            margin: 6px 0;
            font-size: 13px;
            color: #1a1a1a;
            padding: 5px;
            background: rgba(255, 255, 255, 0.4);
            border-radius: 5px;
            transition: all 0.3s ease;
            width: 100%;
            box-sizing: border-box;
            word-wrap: break-word;
        }

        .info-item:hover {
            background: rgba(255, 255, 255, 0.6);
            transform: translateX(5px);
        }

        .info-label {
            font-weight: bold;
            color: #000;
            display: inline-block;
            min-width: 60px;
            word-wrap: break-word;
        }

        .name-value {
            color: #c41e3a;
            font-weight: bold;
            font-size: 14px;
            text-shadow: 1px 1px 2px rgba(255, 255, 255, 0.5);
            word-wrap: break-word;
        }

        .event-details {
            background: linear-gradient(135deg, #e8f5e9 0%, #81c784 50%, #4caf50 100%);
            padding: 15px;
            border-radius: 12px;
            margin: 15px 0;
            text-align: center;
            color: white;
            box-shadow: 0 5px 20px rgba(76, 175, 80, 0.4);
            border: 2px solid #2e7d32;
            position: relative;
            overflow: hidden;
            width: 100%;
            box-sizing: border-box;
        }

        .event-details::before {
            content: '🌿';
            position: absolute;
            top: 8px;
            left: 15px;
            font-size: 25px;
            opacity: 0.3;
        }

        .event-details::after {
            content: '🌿';
            position: absolute;
            bottom: 8px;
            right: 15px;
            font-size: 25px;
            opacity: 0.3;
            transform: rotate(180deg);
        }

        .event-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 8px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
            background: rgba(255, 255, 255, 0.2);
            padding: 6px;
            border-radius: 6px;
            width: 100%;
            box-sizing: border-box;
        }

        .event-info {
            font-size: 13px;
            margin: 5px 0;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
            padding: 3px;
            width: 100%;
            box-sizing: border-box;
            word-wrap: break-word;
        }

        .event-info strong {
            display: inline-block;
            min-width: 60px;
            background: rgba(255, 255, 255, 0.25);
            padding: 2px 8px;
            border-radius: 4px;
        }

        .invitation-message {
            text-align: center;
            font-size: 12px;
            color: #333;
            line-height: 1.6;
            padding: 12px;
            background: linear-gradient(135deg, rgba(255, 235, 205, 0.8), rgba(255, 248, 220, 0.8));
            border-radius: 10px;
            margin-top: 10px;
            border: 2px double #c41e3a;
            box-shadow: 0 3px 15px rgba(196, 30, 58, 0.15);
            position: relative;
            overflow: hidden;
            width: 100%;
            box-sizing: border-box;
        }

        .invitation-message::before,
        .invitation-message::after {
            content: '🪷';
            position: absolute;
            font-size: 18px;
            opacity: 0.6;
            display: none; /* Hidden for compact layout */
        }

        .invitation-message::before {
            top: -8px;
            left: 50%;
            transform: translateX(-50%);
        }

        .invitation-message::after {
            bottom: -8px;
            left: 50%;
            transform: translateX(-50%) rotate(180deg);
        }

        .decorative-element {
            text-align: center;
            font-size: 20px;
            margin: 8px 0;
            position: relative;
            padding: 5px 0;
            width: 100%;
            box-sizing: border-box;
        }

        .decorative-element::before,
        .decorative-element::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 60px;
            height: 1px;
            background: linear-gradient(to right, transparent, #c41e3a, transparent);
            display: none; /* Hidden for compact layout */
        }

        .decorative-element::before {
            right: 70%;
        }

        .decorative-element::after {
            left: 70%;
        }

        /* Alpona style decoration */
        .alpona-pattern {
            text-align: center;
            margin: 8px 0;
            padding: 6px;
            background: radial-gradient(circle, rgba(196, 30, 58, 0.05) 0%, transparent 70%);
            width: 100%;
            box-sizing: border-box;
        }

        .alpona-dots {
            display: inline-block;
            font-size: 10px;
            letter-spacing: 3px;
            color: #c41e3a;
            opacity: 0.7;
            max-width: 100%;
            overflow: hidden;
        }

        @media (max-width: 768px) {
            .couple-info {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .couple-info::before {
                position: static;
                transform: none;
                display: block;
                margin: 8px 0;
                font-size: 18px;
            }

            .vine-left, .vine-right {
                display: none;
            }

            .title {
                font-size: 24px;
            }

            .title::before,
            .title::after {
                display: none;
            }

            .wedding-card {
                padding: 15px;
            }

            .bismillah {
                font-size: 18px;
            }

            .decorative-border {
                padding: 15px;
            }

            .corner-decoration {
                width: 30px;
                height: 30px;
            }

            .decorative-element {
                font-size: 18px;
            }

            .controls h3 {
                font-size: 18px;
            }

            .input-group {
                grid-template-columns: 1fr;
            }

        }
    </style>

    <div class="wedding-card-wrapper">
    <!-- Control Panel -->
    <div class="controls">
        <h3>🎊 কার্ড তথ্য পরিবর্তন করুন 🎊</h3>
        <div class="input-group">
            <div class="input-field">
                <label for="groomName">বরের নাম:</label>
                <input type="text" id="groomName" value="এম, ইসতিয়াক আহমেদ খান">
            </div>
            <div class="input-field">
                <label for="brideName">কনের নাম:</label>
                <input type="text" id="brideName" value="নিলুফা ইয়াসমিন">
            </div>
            <div class="input-field">
                <label for="weddingDate">বিয়ের তারিখ:</label>
                <input type="text" id="weddingDate" value="ঈদের তৃতীয় দিন">
            </div>
        </div>
        <button class="download-btn" onclick="downloadCard()">📥 কার্ড ডাউনলোড করুন</button>
        <button class="reset-btn" onclick="resetData()">🔄 ডিফল্ট রিসেট করুন</button>
    </div>

    <!-- Wedding Card -->
    <div class="card-container" id="weddingCard">
        <div class="wedding-card">
            <div class="decorative-border">
                <!-- Corner Decorations -->
                <div class="corner-decoration corner-top-left"></div>
                <div class="corner-decoration corner-top-right"></div>
                <div class="corner-decoration corner-bottom-left"></div>
                <div class="corner-decoration corner-bottom-right"></div>

                <!-- Header -->
                <div class="header">
                    <div class="bismillah">بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ</div>
                    <div class="title">🌸 বিয়ের দাওয়াত 🌸</div>
                    <div class="subtitle">আপনার উপস্থিতি আমাদের আনন্দকে পূর্ণতা দান করবে</div>
                </div>

                <div class="alpona-pattern">
                    <span class="alpona-dots">• ❀ • ❀ • ❀ • ❀ • ❀ • ❀ •</span>
                </div>

                <div class="decorative-element">🌺 ✿ 🪷 ✿ 🌺</div>

                <!-- Couple Information -->
                <div class="couple-info">
                    <!-- Decorative Vines -->
                    <div class="vine-left"></div>
                    <div class="vine-right"></div>
                    <!-- Groom -->
                    <div class="groom-info">
                        <div class="person-title">🤵 বর 🤵</div>
                        <div class="info-item">
                            <span class="info-label">নাম:</span>
                            <span class="name-value" id="displayGroomName">এম, ইসতিয়াক আহমেদ খান</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">পিতা:</span>
                            <span>নূর-আলাহী খান</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">মাতা:</span>
                            <span>এসমেতারা খানম</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">ঠিকানা:</span>
                            <span>বাঁশিলা, নালডাঙ্গা, নাটোর</span>
                        </div>
                    </div>

                    <!-- Bride -->
                    <div class="bride-info">
                        <div class="person-title">👰 কনে 👰</div>
                        <div class="info-item">
                            <span class="info-label">নাম:</span>
                            <span class="name-value" id="displayBrideName">নিলুফা ইয়াসমিন</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">পিতা:</span>
                            <span>ইয়াসিন প্রো</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">মাতা:</span>
                            <span>মোসা. রোজিনা খাতুন</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">ঠিকানা:</span>
                            <span>বাঁশিলা, নালডাঙ্গা, নাটোর</span>
                        </div>
                    </div>
                </div>

                <div class="alpona-pattern">
                    <span class="alpona-dots">• ✿ • ✿ • ✿ • ✿ • ✿ • ✿ •</span>
                </div>

                <div class="decorative-element">🍃 🌺 🪷 🌺 🍃</div>

                <!-- Event Details -->
                <div class="event-details">
                    <div class="event-title">🎉 অনুষ্ঠানের বিস্তারিত 🎉</div>
                    <div class="event-info">
                        <strong>তারিখ:</strong> <span id="displayDate">ঈদের তৃতীয় দিন</span>
                    </div>
                    <div class="event-info">
                        <strong>সময়:</strong> দুপুর ১২:০০ টা
                    </div>
                    <div class="event-info">
                        <strong>স্থান:</strong> নিজ বাস ভবন
                    </div>
                </div>

                <!-- Invitation Message -->
                <div class="invitation-message">
                    <p>
                        আমরা আপনাকে ও আপনার পরিবারকে আন্তরিকভাবে আমন্ত্রণ জানাচ্ছি<br>
                        আমাদের এই শুভ মুহূর্তে আপনার উপস্থিতি কামনা করছি।<br>
                        <strong>আপনাদের দোয়া ও মঙ্গল কামনা একান্ত প্রত্যাশিত।</strong>
                    </p>
                </div>

                <div class="alpona-pattern">
                    <span class="alpona-dots">• ❀ • ❀ • ❀ • ❀ • ❀ • ❀ •</span>
                </div>

                <div class="decorative-element" style="font-size: 28px;">🌿 💐 শুভেচ্ছা ও ভালোবাসা 💐 🌿</div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
    <script>
        // Load saved values from localStorage on page load
        window.addEventListener('load', function() {
            console.log('Page loaded. html2canvas available:', typeof html2canvas !== 'undefined');
            
            // Load saved values
            const savedGroomName = localStorage.getItem('weddingCardGroomName');
            const savedBrideName = localStorage.getItem('weddingCardBrideName');
            const savedWeddingDate = localStorage.getItem('weddingCardWeddingDate');
            
            // Set input fields and display elements with saved values
            if (savedGroomName) {
                document.getElementById('groomName').value = savedGroomName;
                document.getElementById('displayGroomName').textContent = savedGroomName;
            }
            
            if (savedBrideName) {
                document.getElementById('brideName').value = savedBrideName;
                document.getElementById('displayBrideName').textContent = savedBrideName;
            }
            
            if (savedWeddingDate) {
                document.getElementById('weddingDate').value = savedWeddingDate;
                document.getElementById('displayDate').textContent = savedWeddingDate;
            }
        });

        // Update card and save to localStorage when inputs change
        document.getElementById('groomName').addEventListener('input', function(e) {
            const value = e.target.value;
            document.getElementById('displayGroomName').textContent = value;
            localStorage.setItem('weddingCardGroomName', value);
        });

        document.getElementById('brideName').addEventListener('input', function(e) {
            const value = e.target.value;
            document.getElementById('displayBrideName').textContent = value;
            localStorage.setItem('weddingCardBrideName', value);
        });

        document.getElementById('weddingDate').addEventListener('input', function(e) {
            const value = e.target.value;
            document.getElementById('displayDate').textContent = value;
            localStorage.setItem('weddingCardWeddingDate', value);
        });

        // Download card as image
        function downloadCard() {
            const card = document.getElementById('weddingCard');
            const btn = document.querySelector('.download-btn');
            
            btn.textContent = '⏳ তৈরি হচ্ছে...';
            btn.disabled = true;

            // Wait a bit to ensure all fonts and styles are loaded
            setTimeout(() => {
                if (typeof html2canvas !== 'undefined') {
                    const cardRect = card.getBoundingClientRect();
                    
                    // Temporarily remove box-shadow for cleaner capture
                    const originalBoxShadow = card.style.boxShadow;
                    card.style.boxShadow = 'none';
                    
                    html2canvas(card, {
                        scale: 2,
                        backgroundColor: '#ffffff',
                        logging: true,
                        useCORS: true,
                        allowTaint: true,
                        width: cardRect.width,
                        height: cardRect.height,
                        x: 0,
                        y: 0,
                        scrollX: 0,
                        scrollY: 0,
                        removeContainer: true
                    }).then(canvas => {
                        // Restore box-shadow
                        card.style.boxShadow = originalBoxShadow;
                        
                        const link = document.createElement('a');
                        const groomName = document.getElementById('groomName').value.replace(/\s+/g, '-');
                        const brideName = document.getElementById('brideName').value.replace(/\s+/g, '-');
                        link.download = `বিয়ের-দাওয়াত-${groomName}-${brideName}.png`;
                        link.href = canvas.toDataURL('image/png', 1.0);
                        link.click();
                        
                        btn.textContent = '✅ ডাউনলোড সম্পন্ন!';
                        setTimeout(() => {
                            btn.textContent = '📥 কার্ড ডাউনলোড করুন';
                            btn.disabled = false;
                        }, 2000);
                    }).catch(error => {
                        // Restore box-shadow on error
                        card.style.boxShadow = originalBoxShadow;
                        
                        console.error('Error:', error);
                        btn.textContent = '❌ ত্রুটি হয়েছে: ' + error.message;
                        setTimeout(() => {
                            btn.textContent = '📥 কার্ড ডাউনলোড করুন';
                            btn.disabled = false;
                        }, 3000);
                    });
                } else {
                    btn.textContent = '❌ html2canvas লোড হয়নি';
                    setTimeout(() => {
                        btn.textContent = '📥 কার্ড ডাউনলোড করুন';
                        btn.disabled = false;
                    }, 3000);
                }
            }, 300);
        }

        // Reset data to defaults
        function resetData() {
            // Default values
            const defaultGroomName = 'এম, ইসতিয়াক আহমেদ খান';
            const defaultBrideName = 'নিলুফা ইয়াসমিন';
            const defaultWeddingDate = 'ঈদের তৃতীয় দিন';
            
            // Clear localStorage
            localStorage.removeItem('weddingCardGroomName');
            localStorage.removeItem('weddingCardBrideName');
            localStorage.removeItem('weddingCardWeddingDate');
            
            // Reset input fields
            document.getElementById('groomName').value = defaultGroomName;
            document.getElementById('brideName').value = defaultBrideName;
            document.getElementById('weddingDate').value = defaultWeddingDate;
            
            // Reset display elements
            document.getElementById('displayGroomName').textContent = defaultGroomName;
            document.getElementById('displayBrideName').textContent = defaultBrideName;
            document.getElementById('displayDate').textContent = defaultWeddingDate;
            
            // Visual feedback
            const resetBtn = event.target;
            const originalText = resetBtn.textContent;
            resetBtn.textContent = '✅ রিসেট সম্পন্ন!';
            setTimeout(() => {
                resetBtn.textContent = originalText;
            }, 1500);
        }
    </script>
    </div>
@endsection
