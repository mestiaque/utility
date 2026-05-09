@extends(auth()->check() ? 'me::master' : 'me::guestMaster')

@section('title', 'Calculator')
@section('meta-title', 'Calculator - Calculate Discount and VAT on Price or Amount')
@section('meta-description', 'A simple calculator to calculate discount and VAT on a given price or amount.')
@push('buttons')

@endpush

@section('content')

    <div class="calculator-shell">
        <div class="calculator-hero card border-0 shadow-lg overflow-hidden">
            <div class="calculator-hero__bg"></div>
            <div class="card-body p-4 p-md-5 position-relative">


                <div class="row g-4 align-items-stretch">
                    <div class="col-lg-6">
                        <div class="calculator-panel h-100">
                            <div class="mb-3">
                                <label for="price" class="form-label fw-semibold">Price / Amount</label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text">৳</span>
                                    <input type="number" id="price" class="form-control" placeholder="1000" min="0" step="0.01">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="disc" class="form-label fw-semibold">Discount (%)</label>
                                <div class="input-group input-group-lg">
                                    <input type="number" id="disc" class="form-control" placeholder="10" min="0" max="100" step="0.01">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="vat" class="form-label fw-semibold">VAT (%)</label>
                                <div class="input-group input-group-lg">
                                    <input type="number" id="vat" class="form-control" placeholder="0" min="0" max="100" step="0.01">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>

                            <button id="resetBtn" class="btn btn-outline-light calculator-reset w-100">Reset</button>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="calculator-result h-100">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <h2 class="h6 mb-0" style="letter-spacing: 0.4rem; color:gray"><strong>RESULT</strong></h2>
                                <span class="result-pill">Auto calculated</span>
                            </div>

                            <div class="result-grid">
                                <div>
                                    <div class="result-label">Discount Amount</div>
                                    <div id="discountAmount" class="result-value">৳0.00</div>
                                </div>
                                <div>
                                    <div class="result-label">VAT Amount</div>
                                    <div id="vatAmount" class="result-value">৳0.00</div>
                                </div>
                                <div>
                                    <div class="result-label">Final Payable</div>
                                    <div id="finalAmount" class="result-value result-value--accent">৳0.00</div>
                                </div>
                            </div>

                            <div class="calculator-note mt-4" id="summaryText">
                                Enter an amount to see the final price.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@if(!auth()->check())
  @push('css')
    <style>
        .btn-encodex-active {
            color: #059700;
        }
        .btn-encodex-edit {
            color: rgb(2, 73, 179);
        }
        .btn-encodex-delete {
            color: rgb(177, 2, 2);
        }
    </style>
  @endpush
@endif


@push('css')
    <style>
        .calculator-shell {
            max-width: 980px;
            margin: 0 auto;
        }

        .calculator-hero {
            position: relative;
            border-radius: 24px;
            background: linear-gradient(135deg, #0f172a 0%, #111827 45%, #1f2937 100%);
            color: #e5eefb;
        }

        .calculator-hero__bg {
            position: absolute;
            inset: 0;
            background:
                radial-gradient(circle at top left, rgba(56, 189, 248, 0.22), transparent 32%),
                radial-gradient(circle at bottom right, rgba(16, 185, 129, 0.16), transparent 30%);
            pointer-events: none;
        }

        .calculator-kicker {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            font-size: .78rem;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: #93c5fd;
        }

        .calculator-title {
            font-size: clamp(1.7rem, 3vw, 2.7rem);
            font-weight: 800;
            line-height: 1.05;
            color: #fff;
        }

        .calculator-subtitle {
            color: rgba(229, 238, 251, 0.8);
        }

        .calculator-badge,
        .result-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: .55rem .9rem;
            border-radius: 999px;
            font-size: .85rem;
            font-weight: 700;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #fff;
            backdrop-filter: blur(10px);
        }

        .calculator-panel,
        .calculator-result {
            position: relative;
            z-index: 1;
            padding: 1.25rem;
            border-radius: 20px;
            background: rgba(15, 23, 42, 0.72);
            border: 1px solid rgba(148, 163, 184, 0.18);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.04);
        }

        .calculator-result {
            background: rgba(255, 255, 255, 0.95);
            color: #0f172a;
        }

        .calculator-panel .form-label,
        .calculator-panel .form-text {
            color: rgba(229, 238, 251, 0.82);
        }

        .calculator-panel .form-control,
        .calculator-panel .input-group-text {
            border-color: rgba(148, 163, 184, 0.25);
        }

        .calculator-panel .form-control {
            background: rgba(15, 23, 42, 0.95);
            color: #fff;
        }

        .calculator-panel .form-control::placeholder {
            color: rgba(255, 255, 255, 0.45);
        }

        .calculator-panel .form-control:focus {
            border-color: #38bdf8;
            box-shadow: 0 0 0 .2rem rgba(56, 189, 248, 0.15);
        }

        .calculator-panel .input-group-text {
            background: rgba(30, 41, 59, 0.95);
            color: #e2e8f0;
        }

        .calculator-reset {
            border-color: rgba(255, 255, 255, 0.22);
            color: #fff;
            margin-top: 1rem;
        }

        .calculator-reset:hover {
            background: rgba(255, 255, 255, 0.12);
            border-color: rgba(255, 255, 255, 0.35);
            color: #fff;
        }

        .result-grid {
            display: grid;
            gap: 1rem;
        }

        .result-label {
            font-size: .85rem;
            color: #64748b;
            margin-bottom: .35rem;
        }

        .result-value {
            font-size: 1.55rem;
            font-weight: 800;
            color: #0f172a;
        }

        .result-value--accent {
            color: #0f766e;
        }

        .calculator-note {
            padding: 1rem 1.1rem;
            border-radius: 16px;
            background: linear-gradient(135deg, rgba(15, 118, 110, 0.1), rgba(59, 130, 246, 0.08));
            color: #334155;
            border: 1px solid rgba(148, 163, 184, 0.18);
        }

        @media (max-width: 767.98px) {
            .calculator-shell {
                max-width: 100%;
            }

            .calculator-hero .card-body {
                padding: .8rem !important;
            }

            .calculator-title {
                font-size: 1.45rem;
                line-height: 1.1;
            }

            .calculator-subtitle {
                display: none;
            }

            .calculator-badge,
            .result-pill {
                padding: .35rem .65rem;
                font-size: .72rem;
            }

            .calculator-panel,
            .calculator-result {
                padding: .8rem;
                border-radius: 14px;
            }

            .calculator-panel .form-label {
                margin-bottom: .25rem;
                font-size: .88rem;
            }

            .calculator-panel .form-text {
                margin-top: .2rem;
                font-size: .72rem;
            }

            .calculator-panel .input-group-lg > .form-control,
            .calculator-panel .input-group-lg > .input-group-text,
            .calculator-panel .btn {
                min-height: calc(2.25rem + 2px);
                padding-top: .45rem;
                padding-bottom: .45rem;
                font-size: .92rem;
            }

            .calculator-panel .input-group-lg {
                font-size: .92rem;
            }

            .result-grid {
                gap: .7rem;
            }

            .result-label {
                margin-bottom: .15rem;
                font-size: .72rem;
            }

            .result-value {
                font-size: 1.1rem;
                line-height: 1.15;
            }

            .calculator-note {
                padding: .7rem .8rem;
                border-radius: 12px;
                font-size: .8rem;
                line-height: 1.25;
            }

            #summaryText {
                display: none;
            }
        }
    </style>
@endpush

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const priceInput = document.getElementById('price');
            const discountInput = document.getElementById('disc');
            const vatInput = document.getElementById('vat');
            const discountAmountEl = document.getElementById('discountAmount');
            const vatAmountEl = document.getElementById('vatAmount');
            const finalAmountEl = document.getElementById('finalAmount');
            const summaryTextEl = document.getElementById('summaryText');
            const resetBtn = document.getElementById('resetBtn');

            function formatMoney(value) {
                return `৳${value.toFixed(2)}`;
            }

            function updateCalculator() {
                const price = parseFloat(priceInput.value) || 0;
                const discountPercent = parseFloat(discountInput.value) || 0;
                const vatPercent = parseFloat(vatInput.value) || 0;

                const discountAmount = price * (discountPercent / 100);
                const discountedPrice = Math.max(price - discountAmount, 0);
                const vatAmount = discountedPrice * (vatPercent / 100);
                const finalAmount = discountedPrice + vatAmount;

                discountAmountEl.textContent = formatMoney(discountAmount);
                vatAmountEl.textContent = formatMoney(vatAmount);
                finalAmountEl.textContent = formatMoney(finalAmount);

                // summaryTextEl.textContent = `Price ${formatMoney(price)} - Discount ${discountPercent}% + VAT ${vatPercent}% = Final ${formatMoney(finalAmount)}`;
            }

            [priceInput, discountInput, vatInput].forEach(function (input) {
                input.addEventListener('input', updateCalculator);
            });

            resetBtn.addEventListener('click', function () {
                priceInput.value = '';
                discountInput.value = '';
                vatInput.value = '';
                updateCalculator();
            });

            vatInput.value = '';
            updateCalculator();
        });
    </script>
@endpush
