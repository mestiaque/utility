@php
    $page = $lovePage ?? [];
    $day = $page['day'] ?? 1;
    $theme = $page['theme'] ?? 'ভালোবাসা';
    $emoji = $page['emoji'] ?? '💖';
    $tap = $page['tap'] ?? 'হৃদয়ে ট্যাপ করো';
    $message = $page['message'] ?? 'তোমার জন্য প্রতিটা দিনই একটু বেশি সুন্দর।';
    $interaction = $page['interaction'] ?? 'heart';
    $palette = $page['palette'] ?? ['#26113d', '#ff5f8f', '#ffd36a', '#ffffff'];
    $shape = $page['shape'] ?? 'circle';
    $reveal = $page['reveal'] ?? 'rise';
    $cfgJson = json_encode([
        'day' => $day,
        'theme' => $theme,
        'emoji' => $emoji,
        'message' => $message,
        'interaction' => $interaction,
        'accent' => $palette[1],
        'soft' => $palette[2],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
@endphp
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>দিন {{ $day }} | {{ $theme }}</title>
    <meta name="title" content="দিন {{ $day }} | {{ $theme }}">
    <meta name="description" content="ভালোবাসার ৩০ দিনের সিরিজ - {{ $theme }}।">
    <link rel="icon" href="{{ get_image('app_ico') ?? asset('assets/img/favicon/Encodex.ico') }}" type="image/x-icon">
    <link rel="apple-touch-icon" href="{{ get_image('app_ico') ?? asset('assets/img/favicon/Encodex.ico') }}">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700&family=Hind+Siliguri:wght@400;500;600;700&display=swap');

        :root {
            --bg: {{ $palette[0] }};
            --a: {{ $palette[1] }};
            --b: {{ $palette[2] }};
            --paper: {{ $palette[3] }};
            --ink: #fff8fb;
            --muted: rgba(255, 248, 251, .76);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            min-height: 100vh;
            display: grid;
            place-items: center;
            overflow: hidden;
            color: var(--ink);
            font-family: 'Hind Siliguri', sans-serif;
            background:
                radial-gradient(circle at 16% 16%, color-mix(in srgb, var(--a) 40%, transparent), transparent 34%),
                radial-gradient(circle at 82% 12%, color-mix(in srgb, var(--b) 36%, transparent), transparent 32%),
                radial-gradient(circle at 52% 90%, color-mix(in srgb, var(--paper) 18%, transparent), transparent 38%),
                linear-gradient(135deg, color-mix(in srgb, var(--bg) 88%, black), var(--bg) 58%, color-mix(in srgb, var(--a) 30%, black));
        }

        canvas, .stage {
            position: fixed;
            inset: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
        }

        canvas { z-index: 0; }
        .stage { z-index: 1; overflow: hidden; }

        .card {
            width: min(92vw, 520px);
            min-height: 580px;
            position: relative;
            z-index: 3;
            display: grid;
            align-content: center;
            gap: 16px;
            padding: 28px 22px;
            text-align: center;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, .18);
            background: linear-gradient(155deg, rgba(255, 255, 255, .14), rgba(255, 255, 255, .05));
            box-shadow: 0 28px 70px rgba(0, 0, 0, .42), inset 0 0 60px rgba(255, 255, 255, .04);
            backdrop-filter: blur(14px);
            border-radius: {{ $shape === 'ticket' ? '26px 8px 26px 8px' : ($shape === 'soft' ? '34px' : ($shape === 'stamp' ? '18px' : '24px')) }};
            animation: card{{ ucfirst($reveal) }} .78s cubic-bezier(.2,.8,.2,1) both;
        }

        .card::before {
            content: '';
            position: absolute;
            inset: 10px;
            border: 1px dashed color-mix(in srgb, var(--b) 62%, transparent);
            border-radius: inherit;
            opacity: .62;
            pointer-events: none;
        }

        .badge {
            justify-self: center;
            width: max-content;
            max-width: 100%;
            padding: 6px 13px;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, .2);
            color: color-mix(in srgb, var(--b) 72%, white);
            background: rgba(255, 255, 255, .08);
            font-size: 12px;
        }

        .visual {
            min-height: 168px;
            display: grid;
            place-items: center;
            position: relative;
            isolation: isolate;
        }

        .main-emoji {
            font-size: clamp(76px, 20vw, 118px);
            filter: drop-shadow(0 18px 26px rgba(0,0,0,.24));
            animation: floaty 2.7s ease-in-out infinite;
        }

        .photo {
            width: 112px;
            height: 112px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid color-mix(in srgb, var(--b) 72%, white);
            box-shadow: 0 0 0 9px rgba(255,255,255,.08), 0 20px 34px rgba(0,0,0,.24);
        }

        .title {
            font-family: 'Cinzel', serif;
            font-size: clamp(31px, 8vw, 48px);
            line-height: 1.03;
            letter-spacing: 0;
            color: #fff;
            text-shadow: 0 0 22px color-mix(in srgb, var(--a) 78%, transparent);
        }

        .subtitle {
            color: var(--muted);
            font-size: 15px;
        }

        .message {
            min-height: 124px;
            display: grid;
            place-items: center;
            padding: 15px;
            border-radius: 16px;
            color: var(--paper);
            background: rgba(255,255,255,.075);
            border: 1px solid rgba(255,255,255,.15);
            font-size: clamp(18px, 4vw, 23px);
            line-height: 1.72;
        }

        .tap {
            display: inline-grid;
            place-items: center;
            justify-self: center;
            min-width: 148px;
            min-height: 48px;
            padding: 10px 18px;
            border: 0;
            border-radius: 999px;
            color: color-mix(in srgb, var(--bg) 76%, black);
            background: linear-gradient(135deg, var(--paper), var(--b));
            box-shadow: 0 12px 30px color-mix(in srgb, var(--a) 35%, transparent);
            font: 700 15px 'Hind Siliguri', sans-serif;
            cursor: pointer;
            touch-action: manipulation;
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .tap:active { transform: scale(.94); }
        .tap:hover { box-shadow: 0 16px 38px color-mix(in srgb, var(--a) 52%, transparent); }

        .choice-row {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 16px;
            min-height: 66px;
        }

        .yes, .no {
            min-width: 96px;
            border: 0;
            border-radius: 999px;
            padding: 12px 18px;
            font: 700 16px 'Hind Siliguri', sans-serif;
            cursor: pointer;
        }

        .yes { color: #20111c; background: linear-gradient(135deg, #fff, var(--b)); }
        .no { color: #fff; background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.22); position: relative; }

        .scene {
            width: min(78vw, 330px);
            height: 188px;
            position: relative;
            margin: 0 auto;
        }

        .stem {
            position: absolute;
            bottom: 14px;
            left: calc(50% - 5px);
            width: 10px;
            height: 112px;
            border-radius: 999px;
            background: linear-gradient(90deg, #16854a, #54d987 45%, #13733e);
            transform-origin: bottom;
            animation: growStem 1.4s ease both;
            box-shadow: 0 10px 22px rgba(0, 0, 0, .22);
        }

        .stem::before,
        .stem::after {
            content: '';
            position: absolute;
            width: 42px;
            height: 22px;
            top: 55px;
            border-radius: 100% 0 100% 0;
            background: linear-gradient(135deg, #5ee38c, #168244);
            transform-origin: right center;
        }

        .stem::before {
            right: 7px;
            transform: rotate(-28deg);
        }

        .stem::after {
            left: 7px;
            transform: scaleX(-1) rotate(-28deg);
        }

        .flower {
            position: absolute;
            bottom: 108px;
            left: calc(50% - 62px);
            width: 124px;
            height: 98px;
            transform: scale(.35) rotate(-5deg);
            opacity: 0;
            animation: bloom 1.2s .6s ease forwards;
            filter: drop-shadow(0 16px 18px rgba(0, 0, 0, .22));
        }

        .petal {
            position: absolute;
            background:
                radial-gradient(circle at 34% 24%, rgba(255, 255, 255, .72), transparent 20%),
                linear-gradient(145deg, color-mix(in srgb, var(--paper) 82%, white), var(--a) 62%, color-mix(in srgb, var(--a) 70%, #7d0628));
            box-shadow: inset -8px -10px 16px rgba(119, 4, 39, .22), inset 5px 8px 12px rgba(255,255,255,.18);
            transform-origin: 50% 88%;
        }

        .petal:nth-child(1) {
            width: 58px;
            height: 70px;
            left: 33px;
            top: 3px;
            border-radius: 80% 20% 70% 30% / 72% 28% 72% 28%;
            transform: rotate(-8deg);
            z-index: 3;
        }

        .petal:nth-child(2) {
            width: 66px;
            height: 72px;
            left: 9px;
            top: 19px;
            border-radius: 80% 20% 72% 28% / 64% 34% 66% 36%;
            transform: rotate(-42deg);
            z-index: 2;
        }

        .petal:nth-child(3) {
            width: 66px;
            height: 72px;
            right: 9px;
            top: 19px;
            border-radius: 20% 80% 28% 72% / 34% 64% 36% 66%;
            transform: rotate(42deg);
            z-index: 2;
        }

        .petal:nth-child(4) {
            width: 72px;
            height: 62px;
            left: 4px;
            top: 45px;
            border-radius: 80% 20% 55% 45% / 70% 30% 70% 30%;
            transform: rotate(-18deg);
            z-index: 1;
        }

        .petal:nth-child(5) {
            width: 72px;
            height: 62px;
            right: 4px;
            top: 45px;
            border-radius: 20% 80% 45% 55% / 30% 70% 30% 70%;
            transform: rotate(18deg);
            z-index: 1;
        }

        .core {
            position: absolute;
            left: 39px;
            top: 30px;
            width: 46px;
            height: 48px;
            border-radius: 50%;
            z-index: 4;
            background:
                radial-gradient(ellipse at 67% 32%, rgba(255,255,255,.55), transparent 17%),
                conic-gradient(from 210deg, color-mix(in srgb, var(--a) 60%, #6f0628), var(--paper), var(--a), color-mix(in srgb, var(--a) 70%, #65041f), var(--paper), var(--a));
            box-shadow: inset -7px -9px 12px rgba(96, 0, 30, .26), 0 0 20px color-mix(in srgb, var(--a) 45%, transparent);
        }

        .core::after {
            content: '';
            position: absolute;
            inset: 11px 13px;
            border: 2px solid rgba(255, 255, 255, .42);
            border-left-color: transparent;
            border-bottom-color: transparent;
            border-radius: 50%;
            transform: rotate(-28deg);
        }

        .panda {
            font-size: 96px;
            display: inline-block;
            animation: kiss 2.2s ease-in-out infinite;
        }

        .kiss-heart {
            position: absolute;
            left: 58%;
            top: 28px;
            font-size: 34px;
            animation: kissHeart 2.2s ease-in-out infinite;
        }

        .tree {
            position: absolute;
            bottom: 16px;
            left: calc(50% - 55px);
            width: 110px;
            height: 126px;
        }

        .trunk {
            position: absolute;
            bottom: 0;
            left: 48px;
            width: 17px;
            height: 72px;
            border-radius: 999px;
            background: linear-gradient(#a7673f, #5c2d1e);
        }

        .leaf {
            position: absolute;
            border-radius: 50%;
            background: linear-gradient(135deg, #47d17c, #168047);
            box-shadow: 0 0 28px rgba(70, 209, 124, .3);
        }
        .leaf.a { width: 90px; height: 78px; left: 9px; top: 7px; }
        .leaf.b { width: 72px; height: 66px; left: -8px; top: 36px; }
        .leaf.c { width: 76px; height: 70px; right: -8px; top: 32px; }

        .mini {
            position: absolute;
            z-index: 2;
            animation: fall linear forwards;
            will-change: transform, opacity;
        }

        .reveal-pop { animation: cardZoom .7s cubic-bezier(.18,.9,.2,1.16) both; }
        .hidden { display: none; }

        @keyframes cardRise { from { opacity: 0; transform: translateY(28px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes cardZoom { from { opacity: 0; transform: scale(.86); } to { opacity: 1; transform: scale(1); } }
        @keyframes cardSlide { from { opacity: 0; transform: translateX(-30px); } to { opacity: 1; transform: translateX(0); } }
        @keyframes cardSpin { from { opacity: 0; transform: rotate(-7deg) scale(.92); } to { opacity: 1; transform: rotate(0) scale(1); } }
        @keyframes cardSoft { from { opacity: 0; filter: blur(10px); } to { opacity: 1; filter: blur(0); } }
        @keyframes floaty { 0%,100% { transform: translateY(0) rotate(-2deg); } 50% { transform: translateY(-12px) rotate(2deg); } }
        @keyframes growStem { from { transform: scaleY(0); } to { transform: scaleY(1); } }
        @keyframes bloom { to { opacity: 1; transform: scale(1) rotate(8deg); } }
        @keyframes kiss { 0%,100% { transform: translateX(-8px) rotate(-4deg); } 50% { transform: translateX(12px) rotate(5deg) scale(1.05); } }
        @keyframes kissHeart { 0% { opacity: 0; transform: translate(0, 16px) scale(.7); } 45%,75% { opacity: 1; } 100% { opacity: 0; transform: translate(42px, -34px) scale(1.25); } }
        @keyframes fall { to { transform: translateY(105vh) rotate(420deg); opacity: 0; } }
        @keyframes sparkle { 0%,100% { opacity: .35; transform: scale(.75); } 50% { opacity: 1; transform: scale(1.22); } }
        @keyframes wave { 0%,100% { transform: translateX(-8px); } 50% { transform: translateX(8px); } }

        @media (max-width: 540px) {
            .card { min-height: 540px; padding: 24px 16px; }
            .message { min-height: 148px; }
            .choice-row { gap: 10px; }
            .yes, .no { min-width: 82px; }
        }
    </style>
</head>
<body>
    <canvas id="fx"></canvas>
    <div class="stage" id="stage"></div>

    <section class="card" id="card">
        <span class="badge">Day {{ $day }} • {{ $theme }}</span>

        <div class="visual" id="visual">
            @if($interaction === 'rose')
                <div class="scene" aria-hidden="true">
                    <div class="stem"></div>
                    <div class="flower">
                        <span class="petal"></span><span class="petal"></span><span class="petal"></span><span class="petal"></span><span class="petal"></span><span class="core"></span>
                    </div>
                </div>
            @elseif($interaction === 'panda')
                <span class="panda">🐼</span><span class="kiss-heart">💗</span>
            @elseif($interaction === 'tree')
                <div class="scene" aria-hidden="true">
                    <div class="tree"><span class="leaf a"></span><span class="leaf b"></span><span class="leaf c"></span><span class="trunk"></span></div>
                </div>
            @elseif($interaction === 'photo')
                <img class="photo" src="{{ asset('utility/jan1.png') }}" alt="Love">
            @else
                <span class="main-emoji">{{ $emoji }}</span>
            @endif
        </div>

        <h1 class="title">{{ $theme }}</h1>
        <p class="subtitle">{{ $tap }}</p>
        <p class="message" id="message">{{ $interaction === 'yesno' ? 'তুমি কি আমাকে সবসময় এভাবেই ভালোবাসবে?' : $message }}</p>

        @if($interaction === 'yesno')
            <div class="choice-row">
                <button type="button" class="yes" id="yesBtn">Yes</button>
                <button type="button" class="no" id="noBtn">No</button>
            </div>
        @else
            <button type="button" class="tap" id="tapBtn">{{ $emoji }} ট্যাপ</button>
        @endif
    </section>

    <script>
        const cfg = {!! $cfgJson !!};

        const stage = document.getElementById('stage');
        const msg = document.getElementById('message');
        const tapBtn = document.getElementById('tapBtn');
        const yesBtn = document.getElementById('yesBtn');
        const noBtn = document.getElementById('noBtn');
        const canvas = document.getElementById('fx');
        const ctx = canvas.getContext('2d');
        const dots = [];

        function resize() {
            canvas.width = innerWidth;
            canvas.height = innerHeight;
        }

        addEventListener('resize', resize);
        resize();

        class Dot {
            constructor(x, y, text) {
                this.x = x;
                this.y = y;
                this.text = text || cfg.emoji;
                this.vx = (Math.random() - .5) * 5;
                this.vy = Math.random() * -4 - 1;
                this.life = 1;
                this.size = Math.random() * 18 + 15;
                this.spin = Math.random() * 8 - 4;
            }

            step() {
                this.x += this.vx;
                this.y += this.vy;
                this.vy += .055;
                this.life -= .012;
            }

            draw() {
                ctx.save();
                ctx.globalAlpha = Math.max(this.life, 0);
                ctx.font = `${this.size}px serif`;
                ctx.translate(this.x, this.y);
                ctx.rotate((1 - this.life) * this.spin);
                ctx.fillText(this.text, 0, 0);
                ctx.restore();
            }
        }

        function loop() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            for (let i = dots.length - 1; i >= 0; i -= 1) {
                dots[i].step();
                dots[i].draw();
                if (dots[i].life <= 0) dots.splice(i, 1);
            }
            requestAnimationFrame(loop);
        }
        loop();

        function burst(x, y, icon = cfg.emoji, total = 42) {
            for (let i = 0; i < total; i += 1) dots.push(new Dot(x, y, i % 3 === 0 ? '💖' : icon));
        }

        function typeText(text) {
            msg.textContent = '';
            const units = typeof Intl !== 'undefined' && Intl.Segmenter
                ? Array.from(new Intl.Segmenter('bn', { granularity: 'grapheme' }).segment(text), part => part.segment)
                : Array.from(text);
            let i = 0;
            const timer = setInterval(() => {
                msg.textContent += units[i] || '';
                i += 1;
                if (i >= units.length) clearInterval(timer);
            }, 34);
        }

        function makeMini(icon, left, top, duration) {
            const el = document.createElement('span');
            el.className = 'mini';
            el.textContent = icon;
            el.style.left = `${left}%`;
            el.style.top = `${top ?? -8}%`;
            el.style.fontSize = `${Math.random() * 18 + 18}px`;
            el.style.animationDuration = `${duration ?? (Math.random() * 4 + 5)}s`;
            stage.appendChild(el);
            setTimeout(() => el.remove(), 9000);
        }

        function rain(icon, count = 18) {
            for (let i = 0; i < count; i += 1) {
                setTimeout(() => makeMini(icon, Math.random() * 94 + 2), i * 120);
            }
        }

        function action(e) {
            const r = (e.currentTarget || document.body).getBoundingClientRect();
            const x = r.left + r.width / 2;
            const y = r.top + r.height / 2;
            burst(x, y, cfg.emoji, 54);
            typeText(cfg.message);

            if (cfg.interaction === 'tree') rain('💗', 30);
            if (cfg.interaction === 'rose') rain('🌹', 20);
            if (cfg.interaction === 'panda') rain('💋', 16);
            if (cfg.interaction === 'stars') rain('✨', 24);
            if (cfg.interaction === 'rain') rain('💧', 30);
            if (cfg.interaction === 'butterfly') rain('🦋', 18);
            if (cfg.interaction === 'balloon') rain('🎈', 20);
            if (cfg.interaction === 'music') rain('♪', 24);
        }

        if (tapBtn) tapBtn.addEventListener('click', action);

        if (yesBtn) {
            yesBtn.addEventListener('click', e => {
                msg.textContent = 'আমি জানতাম! এখন এই হৃদয়টা পুরো তোমার।';
                burst(e.clientX || innerWidth / 2, e.clientY || innerHeight / 2, '💞', 80);
                rain('💘', 24);
            });
        }

        if (noBtn) {
            function moveNo() {
                const maxX = Math.max(80, innerWidth - 170);
                const maxY = Math.max(80, innerHeight - 120);
                noBtn.style.position = 'fixed';
                noBtn.style.left = `${Math.random() * maxX + 20}px`;
                noBtn.style.top = `${Math.random() * maxY + 20}px`;
                noBtn.style.zIndex = '9';
                msg.textContent = 'No ধরাই যাবে না, কারণ উত্তরটা Yes হওয়াই লাগে।';
            }
            noBtn.addEventListener('mouseenter', moveNo);
            noBtn.addEventListener('pointerdown', moveNo);
            noBtn.addEventListener('focus', moveNo);
        }

        if (cfg.interaction === 'tree') setInterval(() => makeMini('💗', Math.random() * 24 + 38, 24, 4.6), 520);
        if (cfg.interaction === 'stars') setInterval(() => makeMini('✨', Math.random() * 100, -4, 5.8), 460);
        if (cfg.interaction === 'rain') setInterval(() => makeMini('💧', Math.random() * 100, -4, 3.8), 260);
    </script>
</body>
</html>
