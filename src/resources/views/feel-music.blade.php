@extends('me::master')

@section('title', 'Feel Music')
@push('buttons')
    <section class="top panel">
      <div class="controls">
        <button id="startBtn" class="btn btn-encodex-active pulse">Start</button>
        <button id="modeBtn" disabled class="btn btn-encodex-edit">Bars</button>
        <button id="stopBtn" class="btn btn-encodex-delete hidden">Stop</button>
        <label class="sensitivity" for="sensitivityRange">
          <span>Sensitivity</span>
          <input id="sensitivityRange" type="range" min="0.6" max="2.8" step="0.1" value="1.5" disabled>
        </label>
      </div>
@endpush

@section('content')
  <div class="bg-pulse" aria-hidden="true"></div>

  <main class="app" aria-label="Real-time microphone visualizer app">
    {{-- <section class="top panel">
      <div class="title-wrap">
        <h1>Realtime Mic Visualizer</h1>
        <p>Tap Start, allow mic access, and speak or play music to animate.</p>
      </div>

      <div class="controls">
        <button id="startBtn" class="btn-primary pulse">Start</button>
        <button id="modeBtn" disabled>Mode: Bars</button>
        <button id="stopBtn" class="btn-danger hidden">Stop</button>
        <label class="sensitivity" for="sensitivityRange">
          <span>Sensitivity</span>
          <input id="sensitivityRange" type="range" min="0.6" max="2.8" step="0.1" value="1.5" disabled>
        </label>
      </div>
    </section> --}}

    <section class="viz-wrap panel">
      <button
        id="fullscreenBtn"
        class="fullscreen-btn"
        type="button"
        aria-label="Enter fullscreen"
        title="Fullscreen"
      >
        <span class="icon-enter" aria-hidden="true">
          <svg viewBox="0 0 24 24" focusable="false">
            <path d="M4 9V4h5v2H6v3H4zm10-5h5v5h-2V6h-3V4zM4 15h2v3h3v2H4v-5zm14 0h2v5h-5v-2h3v-3z" />
          </svg>
        </span>
        <span class="icon-exit" aria-hidden="true">
          <svg viewBox="0 0 24 24" focusable="false">
            <path d="M8 4H6v6h6V8H8V4zm8 0v4h-4v2h6V4h-2zM6 14v6h6v-2H8v-4H6zm10 4h-4v2h6v-6h-2v4z" />
          </svg>
        </span>
      </button>
      <canvas id="visualizerCanvas"></canvas>
      <div class="status">
        <div id="statusChip" class="status-chip visible">Press Start to begin</div>
      </div>
    </section>

    <footer class="bottom panel d-none">
      <p id="hintText">Best results: keep volume medium and move device less while viewing.</p>
      <span id="modePill" class="mode-pill">Bars</span>
    </footer>
  </main>
@endsection


@push('css')
  <style>
    :root {
      --bg-1: #040507;
      --bg-2: #0b1020;
      --glass: rgba(255, 255, 255, 0.08);
      --glass-border: rgba(255, 255, 255, 0.2);
      --text: #d8e6ff;
      --muted: #9da9bf;
      --accent: #56d3ff;
      --danger: #ff6f91;
      --ok: #4ef7b2;
      --panel-shadow: 0 20px 60px rgba(0, 0, 0, 0.45);
      --bg-pulse-scale: 1;
      --bg-pulse-opacity: 0.35;
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }



    .bg-pulse {
      position: fixed;
      inset: -20vmax;
      background:
        radial-gradient(circle at 25% 35%, rgba(35, 188, 255, 0.2), transparent 40%),
        radial-gradient(circle at 75% 65%, rgba(126, 76, 255, 0.2), transparent 42%),
        radial-gradient(circle at 50% 50%, rgba(255, 60, 188, 0.08), transparent 55%);
      transform: scale(var(--bg-pulse-scale));
      opacity: var(--bg-pulse-opacity);
      transition: transform 120ms linear, opacity 120ms linear;
      filter: blur(10px) saturate(115%);
      z-index: 0;
      pointer-events: none;
      animation: breathe 8s ease-in-out infinite;
    }

    @keyframes breathe {
      0%, 100% {
        opacity: 0.3;
      }
      50% {
        opacity: 0.45;
      }
    }

    .app {
      position: relative;
      width: 100%;
      height: min(700px, 92dvh);
      display: grid;
      grid-template-rows: auto 1fr auto;
      gap: 0.85rem;
      z-index: 2;
      opacity: 0;
      transform: translateY(12px);
      animation: fade-in 650ms ease-out forwards;
    }

    @keyframes fade-in {
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .panel {
      /* background: linear-gradient(140deg, rgba(255, 255, 255, 0.11), rgba(255, 255, 255, 0.05)); */
      border: 1px solid var(--glass-border);
      border-radius: 18px;
      backdrop-filter: blur(14px) saturate(120%);
      /* box-shadow: var(--panel-shadow); */
    }

    .top {
      /* padding: clamp(0.7rem, 2vw, 1rem) clamp(0.8rem, 2.4vw, 1.2rem); */
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
      flex-wrap: wrap;
    }

    .title-wrap h1 {
      font-size: clamp(1.05rem, 2.8vw, 1.5rem);
      font-weight: 700;
      letter-spacing: 0.2px;
      text-shadow: 0 0 24px rgba(86, 211, 255, 0.28);
    }

    .title-wrap p {
      color: var(--muted);
      margin-top: 0.2rem;
      font-size: clamp(0.72rem, 2.2vw, 0.88rem);
    }

    .controls {
      display: flex;
      align-items: center;
      gap: 0.55rem;
      flex-wrap: wrap;
      justify-content: flex-end;
    }

    button {
      appearance: none;
      border: 1px solid rgba(255, 255, 255, 0.26);
      background: rgba(255, 255, 255, 0.08);
      color: #e8f7ff;
      border-radius: 12px;
      padding: 0.62rem 0.95rem;
      font-weight: 600;
      font-size: 0.9rem;
      letter-spacing: 0.2px;
      cursor: pointer;
      transition: transform 150ms ease, box-shadow 200ms ease, border-color 200ms ease, background 200ms ease;
      box-shadow: 0 0 0 rgba(86, 211, 255, 0);
      will-change: transform;
    }

    button:hover {
      transform: translateY(-1px);
      border-color: rgba(86, 211, 255, 0.75);
      box-shadow: 0 0 24px rgba(86, 211, 255, 0.24);
      background: rgba(86, 211, 255, 0.12);
    }

    button:active {
      transform: translateY(0) scale(0.985);
    }

    button:disabled {
      opacity: 0.55;
      cursor: not-allowed;
      transform: none;
      box-shadow: none;
    }

    .btn-primary {
      background: linear-gradient(135deg, rgba(61, 189, 255, 0.4), rgba(142, 81, 255, 0.25));
      border-color: rgba(114, 186, 255, 0.7);
      box-shadow: 0 0 18px rgba(80, 180, 255, 0.28);
    }

    .btn-danger {
      border-color: rgba(255, 111, 145, 0.75);
      background: rgba(255, 111, 145, 0.13);
    }

    .sensitivity {
      display: flex;
      align-items: center;
      gap: 0.48rem;
      padding: 0.45rem 0.6rem;
      border-radius: 10px;
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.16);
    }

    .sensitivity span {
      color: var(--muted);
      font-size: 0.8rem;
      white-space: nowrap;
    }

    input[type="range"] {
      width: clamp(90px, 16vw, 160px);
      accent-color: #62ddff;
      cursor: pointer;
    }

    .viz-wrap {
      position: relative;
      min-height: 0;
      overflow: hidden;
      border-radius: 20px;
    }

    .fullscreen-btn {
      position: absolute;
      top: clamp(0.55rem, 1.2vw, 0.9rem);
      right: clamp(0.55rem, 1.2vw, 0.9rem);
      z-index: 5;
      width: 2.45rem;
      height: 2.45rem;
      display: grid;
      place-items: center;
      border-radius: 12px;
      border: 0;
      background: linear-gradient(160deg, rgba(4, 10, 22, 0.78), rgba(6, 14, 27, 0.58));
      backdrop-filter: blur(10px);
      color: #e9f6ff;
      box-shadow:
        inset 0 0 0 1px rgba(255, 255, 255, 0.18),
        0 10px 28px rgba(0, 0, 0, 0.42),
        0 0 24px rgba(86, 211, 255, 0.16);
      transition: transform 180ms ease, box-shadow 220ms ease, background 220ms ease;
    }

    .fullscreen-btn::before {
      content: "";
      position: absolute;
      inset: -1px;
      border-radius: inherit;
      background: linear-gradient(120deg, rgba(131, 228, 255, 0.42), rgba(162, 123, 255, 0.22));
      opacity: 0.42;
      z-index: -1;
      filter: blur(0.2px);
    }

    .fullscreen-btn:hover {
      transform: translateY(-1px) scale(1.02);
      background: linear-gradient(160deg, rgba(8, 18, 34, 0.82), rgba(8, 18, 34, 0.62));
      box-shadow:
        inset 0 0 0 1px rgba(255, 255, 255, 0.26),
        0 12px 30px rgba(0, 0, 0, 0.46),
        0 0 28px rgba(86, 211, 255, 0.24);
    }

    .fullscreen-btn:active {
      transform: scale(0.96);
    }

    .fullscreen-btn:focus-visible {
      outline: none;
      box-shadow:
        inset 0 0 0 1px rgba(255, 255, 255, 0.26),
        0 0 0 3px rgba(86, 211, 255, 0.25),
        0 8px 24px rgba(0, 0, 0, 0.45);
    }

    .fullscreen-btn span {
      position: absolute;
      inset: 0;
      display: grid;
      place-items: center;
      transition: opacity 220ms ease, transform 220ms ease;
    }

    .fullscreen-btn svg {
      width: 1.05rem;
      height: 1.05rem;
      fill: currentColor;
    }

    .fullscreen-btn .icon-exit {
      opacity: 0;
      transform: scale(0.72) rotate(-18deg);
    }

    .fullscreen-btn.is-fullscreen .icon-enter {
      opacity: 0;
      transform: scale(0.72) rotate(16deg);
    }

    .fullscreen-btn.is-fullscreen .icon-exit {
      opacity: 1;
      transform: scale(1) rotate(0deg);
    }

    .viz-wrap:fullscreen .fullscreen-btn,
    .viz-wrap:-webkit-full-screen .fullscreen-btn {
      top: max(0.9rem, env(safe-area-inset-top));
      right: max(0.9rem, env(safe-area-inset-right));
    }

    .viz-wrap:fullscreen,
    .viz-wrap:-webkit-full-screen {
      width: 100vw;
      height: 100vh;
      max-width: none;
      max-height: none;
      border-radius: 0;
      margin: 0;
    }

    .viz-wrap:fullscreen canvas,
    .viz-wrap:-webkit-full-screen canvas {
      border-radius: 0;
    }

    canvas {
      width: 100%;
      height: 100%;
      display: block;
      border-radius: 20px;
      background: linear-gradient(180deg, rgba(5, 8, 17, 0.78), rgba(1, 1, 4, 0.88));
    }

    .status {
      position: absolute;
      inset: auto 0 0 0;
      display: grid;
      place-items: center;
      padding: 0.8rem;
      pointer-events: none;
    }

    .status-chip {
      background: rgba(0, 0, 0, 0.46);
      border: 1px solid rgba(255, 255, 255, 0.2);
      color: #e8f6ff;
      border-radius: 999px;
      padding: 0.42rem 0.8rem;
      font-size: 0.82rem;
      letter-spacing: 0.2px;
      box-shadow: 0 0 20px rgba(0, 0, 0, 0.24);
      opacity: 0;
      transform: translateY(8px);
      transition: opacity 220ms ease, transform 220ms ease;
    }

    .status-chip.visible {
      opacity: 1;
      transform: translateY(0);
    }

    .bottom {
      padding: 0.8rem 1rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.8rem;
      flex-wrap: wrap;
      font-size: 0.83rem;
      color: var(--muted);
    }

    .mode-pill {
      color: #bceeff;
      border: 1px solid rgba(86, 211, 255, 0.4);
      border-radius: 999px;
      padding: 0.2rem 0.6rem;
      background: rgba(86, 211, 255, 0.08);
      text-transform: uppercase;
      font-weight: 700;
      letter-spacing: 0.8px;
      font-size: 0.7rem;
    }

    .hidden {
      display: none !important;
    }

    .pulse {
      animation: pulse 1.5s ease-in-out infinite;
    }

    @keyframes pulse {
      0%, 100% {
        box-shadow: 0 0 0 0 rgba(86, 211, 255, 0.32);
      }
      50% {
        box-shadow: 0 0 0 13px rgba(86, 211, 255, 0);
      }
    }

    @media (max-width: 760px) {
      .app {
        width: 96vw;
        height: 94dvh;
        gap: 0.72rem;
      }

      .top {
        flex-direction: column;
        align-items: stretch;
      }

      .controls {
        justify-content: flex-start;
      }

      button {
        padding: 0.6rem 0.8rem;
        font-size: 0.86rem;
      }
    }

    @media (prefers-reduced-motion: reduce) {
      * {
        animation-duration: 1ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 1ms !important;
      }
    }
  </style>
@endpush

@push('js')
  <script>
    (() => {
      "use strict";

      const appState = {
        audioContext: null,
        analyser: null,
        sourceNode: null,
        mediaStream: null,
        dataArray: null,
        bufferLength: 0,
        running: false,
        mode: "bars",
        rafId: 0,
        sensitivity: 1.5,
        smoothedEnergy: 0,
        bassBoost: 0,
        particles: [],
        barPeaks: [],
        barPeakVelocity: []
      };

      const canvas = document.getElementById("visualizerCanvas");
      const vizWrap = document.querySelector(".viz-wrap");
      const ctx = canvas.getContext("2d", { alpha: false, desynchronized: true });
      const startBtn = document.getElementById("startBtn");
      const modeBtn = document.getElementById("modeBtn");
      const stopBtn = document.getElementById("stopBtn");
      const fullscreenBtn = document.getElementById("fullscreenBtn");
      const statusChip = document.getElementById("statusChip");
      const modePill = document.getElementById("modePill");
      const hintText = document.getElementById("hintText");
      const sensitivityRange = document.getElementById("sensitivityRange");
      const bgPulse = document.querySelector(".bg-pulse");

      let devicePixelRatioSafe = Math.min(window.devicePixelRatio || 1, 2);

      function setStatus(message, isError = false) {
        statusChip.textContent = message;
        statusChip.style.borderColor = isError
          ? "rgba(255, 111, 145, 0.8)"
          : "rgba(255, 255, 255, 0.2)";
        statusChip.style.color = isError ? "#ffdce6" : "#e8f6ff";
        statusChip.classList.add("visible");
      }

      function hideStatus() {
        statusChip.classList.remove("visible");
      }

      function resizeCanvas() {
        const rect = canvas.getBoundingClientRect();
        const width = Math.max(1, Math.floor(rect.width * devicePixelRatioSafe));
        const height = Math.max(1, Math.floor(rect.height * devicePixelRatioSafe));

        if (canvas.width !== width || canvas.height !== height) {
          canvas.width = width;
          canvas.height = height;
        }
      }

      function getFullscreenElement() {
        return (
          document.fullscreenElement ||
          document.webkitFullscreenElement ||
          null
        );
      }

      function syncFullscreenUi() {
        const isFullscreen = !!getFullscreenElement();
        fullscreenBtn.classList.toggle("is-fullscreen", isFullscreen);
        fullscreenBtn.setAttribute(
          "aria-label",
          isFullscreen ? "Exit fullscreen" : "Enter fullscreen"
        );
        fullscreenBtn.setAttribute(
          "title",
          isFullscreen ? "Exit fullscreen" : "Fullscreen"
        );
      }

      async function lockLandscape() {
        try {
          if (screen.orientation && typeof screen.orientation.lock === "function") {
            await screen.orientation.lock("landscape");
          }
        } catch (_) {
          // orientation lock is not supported or not allowed — ignore silently
        }
      }

      async function unlockOrientation() {
        try {
          if (screen.orientation && typeof screen.orientation.unlock === "function") {
            screen.orientation.unlock();
          }
        } catch (_) {}
      }

      async function toggleFullscreen() {
        if (!vizWrap) return;

        const activeFullscreenElement = getFullscreenElement();
        try {
          if (activeFullscreenElement) {
            if (document.exitFullscreen) {
              await document.exitFullscreen();
            } else if (document.webkitExitFullscreen) {
              document.webkitExitFullscreen();
            }
            await unlockOrientation();
            setStatus("Exited fullscreen");
          } else if (vizWrap.requestFullscreen) {
            await vizWrap.requestFullscreen({ navigationUI: "hide" });
            await lockLandscape();
            setStatus("Fullscreen enabled");
          } else if (vizWrap.webkitRequestFullscreen) {
            vizWrap.webkitRequestFullscreen();
            await lockLandscape();
            setStatus("Fullscreen enabled");
          } else {
            setStatus("Fullscreen is not supported in this browser.", true);
            return;
          }

          resizeCanvas();
          setTimeout(hideStatus, 700);
        } catch (error) {
          setStatus("Could not switch fullscreen mode.", true);
        }
      }

      function safeStopTracks(stream) {
        if (!stream) return;
        stream.getTracks().forEach((track) => track.stop());
      }

      function getUserMediaCompat(constraints) {
        // Modern path.
        if (navigator.mediaDevices && typeof navigator.mediaDevices.getUserMedia === "function") {
          return navigator.mediaDevices.getUserMedia(constraints);
        }

        // Legacy path for older browsers.
        const legacyGetUserMedia =
          navigator.getUserMedia ||
          navigator.webkitGetUserMedia ||
          navigator.mozGetUserMedia ||
          navigator.msGetUserMedia;

        if (!legacyGetUserMedia) {
          return Promise.reject(new Error("GUM_NOT_SUPPORTED"));
        }

        return new Promise((resolve, reject) => {
          legacyGetUserMedia.call(navigator, constraints, resolve, reject);
        });
      }

      function getMicrophoneUnavailableReason() {
        const isLocalhost =
          location.hostname === "localhost" ||
          location.hostname === "127.0.0.1" ||
          location.hostname === "::1";

        if (!window.isSecureContext && !isLocalhost) {
          return "Microphone requires HTTPS (or localhost). Open this page over HTTPS and retry.";
        }

        return "Microphone API is unavailable in this browser/device.";
      }

      function resetParticles(count = 40) {
        appState.particles.length = 0;
        const countSafe = window.innerWidth < 640 ? Math.min(count, 26) : count;
        for (let i = 0; i < countSafe; i += 1) {
          appState.particles.push({
            x: Math.random() * canvas.width,
            y: Math.random() * canvas.height,
            size: 1 + Math.random() * 2.2,
            alpha: 0.16 + Math.random() * 0.36,
            speedX: -0.4 + Math.random() * 0.8,
            speedY: -0.4 + Math.random() * 0.8
          });
        }
      }

      function getAverageRange(from, to) {
        const arr = appState.dataArray;
        let total = 0;
        let count = 0;
        const end = Math.min(to, arr.length);
        for (let i = from; i < end; i += 1) {
          total += arr[i];
          count += 1;
        }
        return count ? total / count : 0;
      }

      function calculateEnergy() {
        const arr = appState.dataArray;
        let sum = 0;
        for (let i = 0; i < arr.length; i += 4) {
          sum += arr[i];
        }
        return (sum / (arr.length / 4 || 1)) / 255;
      }

      function getDynamicHue(energy) {
        return (190 + energy * 120 + appState.bassBoost * 90) % 360;
      }

      function drawParticles(energy) {
        if (!appState.particles.length) return;

        const speedMult = 0.35 + energy * 2.2;
        for (const p of appState.particles) {
          p.x += p.speedX * speedMult;
          p.y += p.speedY * speedMult;

          if (p.x < 0) p.x = canvas.width;
          if (p.x > canvas.width) p.x = 0;
          if (p.y < 0) p.y = canvas.height;
          if (p.y > canvas.height) p.y = 0;

          const alpha = Math.min(0.9, p.alpha + energy * 0.5 + appState.bassBoost * 0.45);
          ctx.beginPath();
          ctx.fillStyle = `hsla(${getDynamicHue(energy)}, 100%, 70%, ${alpha})`;
          ctx.arc(p.x, p.y, p.size + energy * 2.1, 0, Math.PI * 2);
          ctx.fill();
        }
      }

      function drawBars(energy) {
        const width = canvas.width;
        const height = canvas.height;
        const bars = Math.max(24, Math.min(96, Math.floor(width / 18)));
        const step = Math.max(1, Math.floor(appState.bufferLength / bars));
        const gap = Math.max(1.5, width * 0.002);
        const totalGap = gap * (bars - 1);
        const barWidth = (width - totalGap) / bars;
        const capHeight = Math.max(2, Math.floor(height * 0.008));
        const capSpacing = Math.max(2, Math.floor(height * 0.004));
        const gravity = Math.max(0.2, height * 0.00085);
        let x = 0;

        // Keep peak arrays in sync with current bar count.
        if (appState.barPeaks.length !== bars) {
          appState.barPeaks = new Array(bars).fill(height - capHeight);
          appState.barPeakVelocity = new Array(bars).fill(0);
        }

        ctx.shadowBlur = 22 + energy * 36;
        ctx.shadowColor = `hsla(${getDynamicHue(energy)}, 100%, 65%, 0.95)`;

        for (let i = 0; i < bars; i += 1) {
          const idx = i * step;
          const raw = appState.dataArray[idx] / 255;
          const h = Math.max(4, raw * height * 0.82 * appState.sensitivity + appState.bassBoost * 28);
          const y = height - h;

          const hue = (getDynamicHue(energy) + i * 0.9) % 360;
          const gradient = ctx.createLinearGradient(0, y, 0, y + h);
          gradient.addColorStop(0, `hsla(${hue}, 100%, 70%, 1)`);
          gradient.addColorStop(1, `hsla(${(hue + 50) % 360}, 100%, 45%, 0.85)`);
          ctx.fillStyle = gradient;
          ctx.fillRect(x, y, Math.max(1, barWidth), h);

          // Peak cap behavior:
          // - When bar rises, cap is pushed upward.
          // - Otherwise cap falls down gradually.
          const targetCapY = Math.max(0, y - capSpacing - capHeight);
          if (targetCapY < appState.barPeaks[i]) {
            appState.barPeaks[i] = targetCapY;
            appState.barPeakVelocity[i] = 0;
          } else {
            appState.barPeakVelocity[i] += gravity;
            appState.barPeaks[i] += appState.barPeakVelocity[i];
            const floorY = height - capHeight;
            if (appState.barPeaks[i] > floorY) {
              appState.barPeaks[i] = floorY;
              appState.barPeakVelocity[i] = 0;
            }
          }

          const capHue = (hue + 18) % 360;
          ctx.fillStyle = `hsla(${capHue}, 100%, 82%, 0.98)`;
          ctx.fillRect(
            x,
            appState.barPeaks[i],
            Math.max(1, barWidth),
            capHeight
          );

          x += barWidth + gap;
        }

        ctx.shadowBlur = 0;
      }

      function drawRadial(energy) {
        const width = canvas.width;
        const height = canvas.height;
        const cx = width / 2;
        const cy = height / 2;
        const minSide = Math.min(width, height);
        const baseRadius = minSide * 0.19;
        const ringCount = Math.max(36, Math.min(140, Math.floor(appState.bufferLength / 6)));

        ctx.save();
        ctx.translate(cx, cy);
        ctx.rotate(performance.now() * 0.00012);

        ctx.shadowBlur = 28 + energy * 48;
        ctx.shadowColor = `hsla(${getDynamicHue(energy)}, 100%, 72%, 0.92)`;

        for (let i = 0; i < ringCount; i += 1) {
          const idx = Math.floor((i / ringCount) * appState.bufferLength);
          const power = appState.dataArray[idx] / 255;
          const angle = (Math.PI * 2 * i) / ringCount;
          const amp = power * minSide * 0.26 * appState.sensitivity + appState.bassBoost * 18;
          const r1 = baseRadius;
          const r2 = baseRadius + amp;

          const x1 = Math.cos(angle) * r1;
          const y1 = Math.sin(angle) * r1;
          const x2 = Math.cos(angle) * r2;
          const y2 = Math.sin(angle) * r2;

          const hue = (getDynamicHue(energy) + i * 1.6) % 360;
          ctx.strokeStyle = `hsla(${hue}, 100%, ${58 + power * 20}%, ${0.75 + power * 0.25})`;
          ctx.lineWidth = Math.max(1.3, minSide * 0.0025 + power * 3.8);
          ctx.beginPath();
          ctx.moveTo(x1, y1);
          ctx.lineTo(x2, y2);
          ctx.stroke();
        }

        const coreRadius = baseRadius * (0.75 + appState.bassBoost * 0.34);
        const coreGradient = ctx.createRadialGradient(0, 0, 4, 0, 0, coreRadius * 1.4);
        coreGradient.addColorStop(0, `hsla(${getDynamicHue(energy)}, 100%, 75%, 0.9)`);
        coreGradient.addColorStop(1, "hsla(0, 0%, 0%, 0)");
        ctx.fillStyle = coreGradient;
        ctx.beginPath();
        ctx.arc(0, 0, coreRadius * 1.4, 0, Math.PI * 2);
        ctx.fill();

        ctx.restore();
        ctx.shadowBlur = 0;
      }

      function drawFrame() {
        if (!appState.running || !appState.analyser) return;

        appState.analyser.getByteFrequencyData(appState.dataArray);

        const energy = calculateEnergy();
        appState.smoothedEnergy += (energy - appState.smoothedEnergy) * 0.16;

        // Bass detection from low frequency bins. Strong bass causes short boost animation.
        const bass = getAverageRange(0, Math.floor(appState.bufferLength * 0.07)) / 255;
        const bassTrigger = bass > 0.5 ? (bass - 0.5) * 2.2 : 0;
        appState.bassBoost += (bassTrigger - appState.bassBoost) * 0.2;
        appState.bassBoost = Math.max(0, Math.min(1, appState.bassBoost));

        const width = canvas.width;
        const height = canvas.height;
        const hue = getDynamicHue(appState.smoothedEnergy);

        const bgGrad = ctx.createLinearGradient(0, 0, 0, height);
        bgGrad.addColorStop(0, `hsla(${hue}, 45%, 10%, 0.8)`);
        bgGrad.addColorStop(1, "rgba(0, 0, 0, 0.94)");
        ctx.fillStyle = bgGrad;
        ctx.fillRect(0, 0, width, height);

        drawParticles(appState.smoothedEnergy);

        if (appState.mode === "bars") {
          drawBars(appState.smoothedEnergy);
        } else {
          drawRadial(appState.smoothedEnergy);
        }

        // Audio-synced background pulse for premium ambient feel.
        const pulseScale = 1 + appState.smoothedEnergy * 0.06 + appState.bassBoost * 0.08;
        const pulseOpacity = 0.22 + appState.smoothedEnergy * 0.34 + appState.bassBoost * 0.2;
        document.documentElement.style.setProperty("--bg-pulse-scale", pulseScale.toFixed(3));
        document.documentElement.style.setProperty("--bg-pulse-opacity", pulseOpacity.toFixed(3));

        appState.rafId = requestAnimationFrame(drawFrame);
      }

      async function startVisualizer() {
        if (appState.running) return;

        setStatus("Initializing microphone...");
        startBtn.disabled = true;
        startBtn.classList.remove("pulse");

        try {
          const stream = await getUserMediaCompat({ audio: true, video: false });
          const audioContext = new (window.AudioContext || window.webkitAudioContext)();
          const analyser = audioContext.createAnalyser();

          analyser.fftSize = window.innerWidth < 640 ? 1024 : 2048;
          analyser.smoothingTimeConstant = 0.82;
          analyser.minDecibels = -92;
          analyser.maxDecibels = -14;

          const sourceNode = audioContext.createMediaStreamSource(stream);
          sourceNode.connect(analyser);

          appState.mediaStream = stream;
          appState.audioContext = audioContext;
          appState.analyser = analyser;
          appState.sourceNode = sourceNode;
          appState.bufferLength = analyser.frequencyBinCount;
          appState.dataArray = new Uint8Array(appState.bufferLength);
          appState.running = true;
          appState.smoothedEnergy = 0;
          appState.bassBoost = 0;

          resetParticles();
          resizeCanvas();

          modeBtn.disabled = false;
          stopBtn.classList.remove("hidden");
        //   startBtn.classList.add("hidden")
          sensitivityRange.disabled = false;

          hintText.textContent = "Live mic active. Switch modes or adjust sensitivity.";
          setStatus("Mic connected");
          setTimeout(hideStatus, 900);

          appState.rafId = requestAnimationFrame(drawFrame);
        } catch (error) {
          const denied =
            error &&
            (error.name === "NotAllowedError" ||
              error.name === "PermissionDeniedError" ||
              error.name === "SecurityError");

          if (denied) {
            setStatus("Microphone permission denied. Please allow access and retry.", true);
          } else if (
            error &&
            (error.message === "GUM_NOT_SUPPORTED" ||
              error.name === "NotSupportedError" ||
              error.name === "TypeError")
          ) {
            setStatus(getMicrophoneUnavailableReason(), true);
          } else {
            setStatus("Could not start microphone. Check device audio settings.", true);
          }

          startBtn.disabled = false;
          startBtn.classList.add("pulse");
        }
      }

      async function stopVisualizer() {
        appState.running = false;
        cancelAnimationFrame(appState.rafId);
        safeStopTracks(appState.mediaStream);

        if (appState.sourceNode) {
          try {
            appState.sourceNode.disconnect();
          } catch (error) {
            // ignore disconnect errors
          }
        }

        if (appState.analyser) {
          try {
            appState.analyser.disconnect();
          } catch (error) {
            // ignore disconnect errors
          }
        }

        if (appState.audioContext) {
          try {
            await appState.audioContext.close();
          } catch (error) {
            // ignore close errors
          }
        }

        appState.mediaStream = null;
        appState.audioContext = null;
        appState.analyser = null;
        appState.sourceNode = null;
        appState.dataArray = null;
        appState.bufferLength = 0;
        appState.smoothedEnergy = 0;
        appState.bassBoost = 0;

        startBtn.disabled = false;
        startBtn.classList.add("pulse");
        modeBtn.disabled = true;
        stopBtn.classList.add("hidden");
        // startBtn.classList.remove("hidden");
        sensitivityRange.disabled = true;
        hintText.textContent = "Visualizer stopped. Press Start to enable microphone again.";
        setStatus("Stopped");
        setTimeout(hideStatus, 1200);

        ctx.fillStyle = "#04060d";
        ctx.fillRect(0, 0, canvas.width, canvas.height);
      }

      function toggleMode() {
        appState.mode = appState.mode === "bars" ? "radial" : "bars";
        const label = appState.mode === "bars" ? "Bars" : "Circle";
        modeBtn.textContent = `${label}`;
        modePill.textContent = label;
        setStatus(`Switched to ${label} mode`);
        setTimeout(hideStatus, 650);
      }

      function updateSensitivity() {
        appState.sensitivity = Number(sensitivityRange.value);
      }

      function handleVisibility() {
        if (!appState.audioContext) return;
        if (document.hidden) {
          appState.audioContext.suspend().catch(() => {});
        } else if (appState.running) {
          appState.audioContext.resume().catch(() => {});
        }
      }

      startBtn.addEventListener("click", startVisualizer);
      stopBtn.addEventListener("click", stopVisualizer);
      modeBtn.addEventListener("click", toggleMode);
      sensitivityRange.addEventListener("input", updateSensitivity);
      document.addEventListener("visibilitychange", handleVisibility);
      fullscreenBtn.addEventListener("click", toggleFullscreen);
      document.addEventListener("fullscreenchange", () => {
        syncFullscreenUi();
        resizeCanvas();
      });
      document.addEventListener("webkitfullscreenchange", () => {
        syncFullscreenUi();
        resizeCanvas();
      });

      window.addEventListener("resize", () => {
        devicePixelRatioSafe = Math.min(window.devicePixelRatio || 1, 2);
        resizeCanvas();
        if (!appState.running) {
          resetParticles();
          ctx.fillStyle = "#04060d";
          ctx.fillRect(0, 0, canvas.width, canvas.height);
        }
      });

      resizeCanvas();
      syncFullscreenUi();
      resetParticles();
      ctx.fillStyle = "#04060d";
      ctx.fillRect(0, 0, canvas.width, canvas.height);
    })();
  </script>
@endpush
