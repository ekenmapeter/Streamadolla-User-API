<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Streamadollar — Get Paid to Listen. Artists Get Real Plays.</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --neu-bg: #e8edf4;
            --neu-soft: #dde3ee;
            --neu-shadow-dark: rgba(163, 177, 198, 0.65);
            --neu-shadow-light: rgba(255, 255, 255, 0.95);
            --neu-text: #4b5a72;
            --neu-text-strong: #2d3a52;
            --neu-accent: #6c63ff;
            --neu-accent-soft: #eceaff;
        }

        * {
            font-family: 'Plus Jakarta Sans', sans-serif;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            background: var(--neu-bg);
            color: var(--neu-text);
        }

        /* ── Neumorphic primitives ── */
        .neu {
            background: var(--neu-bg);
            border-radius: 28px;
            box-shadow: 10px 10px 24px var(--neu-shadow-dark), -10px -10px 24px var(--neu-shadow-light);
        }

        .neu-sm {
            background: var(--neu-bg);
            border-radius: 18px;
            box-shadow: 6px 6px 14px var(--neu-shadow-dark), -6px -6px 14px var(--neu-shadow-light);
        }

        .neu-inset {
            background: var(--neu-bg);
            border-radius: 22px;
            box-shadow: inset 6px 6px 14px var(--neu-shadow-dark), inset -6px -6px 14px var(--neu-shadow-light);
        }

        .neu-btn {
            background: var(--neu-bg);
            border-radius: 999px;
            box-shadow: 6px 6px 14px var(--neu-shadow-dark), -6px -6px 14px var(--neu-shadow-light);
            transition: all .2s ease;
            cursor: pointer;
        }

        .neu-btn:hover {
            transform: translateY(-2px);
        }

        .neu-btn:active {
            transform: translateY(0);
            box-shadow: inset 5px 5px 12px var(--neu-shadow-dark), inset -5px -5px 12px var(--neu-shadow-light);
        }

        .neu-accent {
            background: linear-gradient(145deg, #7a70ff, #5b52e8);
            border-radius: 999px;
            box-shadow: 6px 6px 14px rgba(108, 99, 255, .45), -6px -6px 14px var(--neu-shadow-light);
            transition: all .2s ease;
            cursor: pointer;
        }

        .neu-accent:hover {
            transform: translateY(-2px);
            box-shadow: 8px 8px 18px rgba(108, 99, 255, .5), -8px -8px 18px var(--neu-shadow-light);
        }

        .neu-accent:active {
            transform: translateY(0);
            box-shadow: inset 5px 5px 12px rgba(40, 30, 140, .35), inset -5px -5px 12px rgba(255, 255, 255, .25);
        }

        .neu-circle {
            background: var(--neu-bg);
            border-radius: 50%;
            box-shadow: 5px 5px 12px var(--neu-shadow-dark), -5px -5px 12px var(--neu-shadow-light);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .neu-step {
            background: var(--neu-bg);
            border-radius: 24px;
            box-shadow: 8px 8px 18px var(--neu-shadow-dark), -8px -8px 18px var(--neu-shadow-light);
            transition: transform .25s ease, box-shadow .25s ease;
        }

        .neu-step:hover {
            transform: translateY(-6px);
            box-shadow: 12px 12px 26px var(--neu-shadow-dark), -12px -12px 26px var(--neu-shadow-light);
        }

        .text-gradient {
            background: linear-gradient(120deg, #6c63ff, #a855f7, #ec4899);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .platform-dot {
            box-shadow: inset 3px 3px 7px var(--neu-shadow-dark), inset -3px -3px 7px var(--neu-shadow-light);
        }

        .faq-answer {
            max-height: 0;
            overflow: hidden;
            transition: max-height .35s ease;
        }

        .faq-open .faq-answer {
            max-height: 300px;
        }

        .faq-open .faq-icon {
            transform: rotate(45deg);
        }

        .faq-icon {
            transition: transform .3s ease;
        }

        .ticker {
            animation: ticker 18s linear infinite;
            white-space: nowrap;
        }

        @keyframes ticker {
            from { transform: translateX(0); }
            to { transform: translateX(-50%); }
        }

        .equalizer span {
            display: inline-block;
            width: 4px;
            border-radius: 99px;
            background: var(--neu-accent);
            animation: eq 1s ease-in-out infinite;
        }

        @keyframes eq {
            0%, 100% { height: 6px; }
            50% { height: 18px; }
        }
    </style>
</head>

<body class="min-h-screen">
    <!-- ═══════════ NAV ═══════════ -->
    @include('_partials.guest-nav')

    <!-- ═══════════ HERO ═══════════ -->
    <header class="max-w-6xl mx-auto px-6 pt-16 pb-14">
        <div class="grid lg:grid-cols-2 gap-14 items-center">
            <div class="text-center lg:text-left">
                <h1 class="text-5xl md:text-6xl xl:text-7xl font-extrabold leading-[1.08] mb-6" style="color: var(--neu-text-strong)">
                    Get Paid to<br>
                    <span class="text-gradient">Listen to Music</span>
                </h1>

                <p class="text-lg md:text-xl font-medium max-w-xl mx-auto lg:mx-0 mb-12 leading-relaxed">
                    We seed fresh tracks into your feed on Spotify, YouTube, YouTube Music, Audiomark &amp; more.
                    Listen the way you already do — and earn <span class="font-bold" style="color: var(--neu-accent)">real dollars</span> for every play.
                </p>

                <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-5 mb-14">
                    <a href="{{ asset('download/streamadolla-official-with-mutiple-device-v5.apk') }}" class="neu-accent px-10 py-4 text-base font-extrabold text-white">
                        <i class="fas fa-mobile-screen-button mr-2"></i>Get the App &amp; Start Earning
                    </a>
                    <a href="#how" class="neu-btn px-10 py-4 text-base font-bold" style="color: var(--neu-text-strong)">
                        <i class="fas fa-route mr-2"></i>See the Workflow
                    </a>
                </div>
            </div>

            <!-- Hero image -->
            <div class="relative max-w-md mx-auto lg:max-w-none">
                <div class="neu p-4">
                    <img
                        src="https://images.unsplash.com/photo-1505740420928-5e560c06d30e?q=80&w=1200&auto=format&fit=crop"
                        alt="Person listening to music on wireless headphones"
                        class="rounded-3xl w-full object-cover aspect-[4/3]"
                        loading="eager">
                </div>
                <div class="neu-sm absolute -top-6 -left-4 md:-left-8 px-5 py-3 flex items-center space-x-3">
                    <div class="neu-circle h-9 w-9 text-green-500"><i class="fas fa-check text-sm"></i></div>
                    <div class="text-left leading-tight">
                        <p class="text-xs font-extrabold" style="color: var(--neu-text-strong)">Play verified</p>
                        <p class="text-sm font-extrabold text-gradient">+$0.04 earned</p>
                    </div>
                </div>
                <div class="neu-sm absolute -bottom-6 -right-4 md:-right-8 px-5 py-3 flex items-center space-x-3">
                    <div class="neu-circle h-9 w-9 text-sm font-extrabold text-gradient">2.1M</div>
                    <div class="text-left leading-tight">
                        <p class="text-xs font-extrabold" style="color: var(--neu-text-strong)">Plays delivered</p>
                        <p class="text-xs font-semibold opacity-70">across all platforms</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Platform row -->
        <div class="flex flex-wrap items-center justify-center gap-4 mt-16 mb-16">
            <span class="text-xs font-bold uppercase tracking-widest opacity-60 mr-2">Supported platforms</span>
            <div class="neu-circle h-14 w-14" title="Spotify"><i class="fab fa-spotify text-2xl text-[#1DB954]"></i></div>
            <div class="neu-circle h-14 w-14" title="YouTube"><i class="fab fa-youtube text-2xl text-[#FF0000]"></i></div>
            <div class="neu-circle h-14 w-14" title="YouTube Music"><i class="fas fa-music text-2xl text-[#FF0033]"></i></div>
            <div class="neu-circle h-14 w-14" title="Audiomark"><i class="fas fa-waveform text-2xl" style="color: var(--neu-accent)"></i></div>
            <div class="neu-circle h-14 w-14" title="And many more…"><i class="fas fa-plus text-xl" style="color: var(--neu-text)"></i></div>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 max-w-4xl mx-auto">
            <div class="neu p-6">
                <p class="text-3xl font-extrabold text-gradient">$48k+</p>
                <p class="text-xs font-semibold mt-1 opacity-70">Paid out to listeners</p>
            </div>
            <div class="neu p-6">
                <p class="text-3xl font-extrabold text-gradient">12,400+</p>
                <p class="text-xs font-semibold mt-1 opacity-70">Active listeners</p>
            </div>
            <div class="neu p-6">
                <p class="text-3xl font-extrabold text-gradient">2.1M+</p>
                <p class="text-xs font-semibold mt-1 opacity-70">Plays delivered</p>
            </div>
            <div class="neu p-6">
                <p class="text-3xl font-extrabold text-gradient">24/7</p>
                <p class="text-xs font-semibold mt-1 opacity-70">Fresh tracks, always</p>
            </div>
        </div>
    </header>

    <!-- ═══════════ WORKFLOW ═══════════ -->
    <section id="how" class="max-w-6xl mx-auto px-6 pb-24 scroll-mt-8">
        <h2 class="text-3xl md:text-4xl font-extrabold text-center mb-3" style="color: var(--neu-text-strong)">
            The <span class="text-gradient">Streamadollar</span> Workflow
        </h2>
        <p class="text-center font-medium max-w-xl mx-auto mb-14">Two sides, one loop — listeners earn, artists get genuine plays.</p>

        <div class="grid lg:grid-cols-2 gap-10">
            <!-- Listener flow -->
            <div>
                <div class="neu-inset inline-flex items-center space-x-2 px-5 py-2.5 rounded-full text-sm font-extrabold mb-8" style="color: var(--neu-accent)">
                    <i class="fas fa-headphones"></i><span>FOR LISTENERS</span>
                </div>
                <div class="space-y-8">
                    <div class="neu-step p-7 flex items-start space-x-5">
                        <div class="neu-circle h-12 w-12 shrink-0 text-lg font-extrabold text-gradient">1</div>
                        <div>
                            <h3 class="font-extrabold text-lg mb-1" style="color: var(--neu-text-strong)">Get the app &amp; create your free account</h3>
                            <p class="text-sm font-medium leading-relaxed">Download the Streamadollar app, sign up in seconds and link the streaming platforms you already use — Spotify, YouTube, YouTube Music, Audiomark and more. The app is how all earning starts.</p>
                        </div>
                    </div>
                    <div class="neu-step p-7 flex items-start space-x-5">
                        <div class="neu-circle h-12 w-12 shrink-0 text-lg font-extrabold text-gradient">2</div>
                        <div>
                            <h3 class="font-extrabold text-lg mb-1" style="color: var(--neu-text-strong)">Tracks get seeded to your feed</h3>
                            <p class="text-sm font-medium leading-relaxed">Artists' campaigns drop songs straight into your playlists and recommendations. Nothing to download, nothing to search.</p>
                        </div>
                    </div>
                    <div class="neu-step p-7 flex items-start space-x-5">
                        <div class="neu-circle h-12 w-12 shrink-0 text-lg font-extrabold text-gradient">3</div>
                        <div>
                            <h3 class="font-extrabold text-lg mb-1" style="color: var(--neu-text-strong)">Listen naturally — plays are tracked</h3>
                            <p class="text-sm font-medium leading-relaxed">Stream the song like you normally would. Our system verifies the play, watch-time and platform activity — no bots, no shortcuts.</p>
                        </div>
                    </div>
                    <div class="neu-step p-7 flex items-start space-x-5">
                        <div class="neu-circle h-12 w-12 shrink-0 text-lg font-extrabold text-gradient">4</div>
                        <div>
                            <h3 class="font-extrabold text-lg mb-1" style="color: var(--neu-text-strong)">Earn &amp; withdraw, anytime</h3>
                            <p class="text-sm font-medium leading-relaxed">Every verified play adds dollars to your wallet. Withdraw via bank transfer, mobile money or crypto whenever you want.</p>
                        </div>
                    </div>
                </div>
                <a href="{{ asset('download/streamadolla-official-with-mutiple-device-v5.apk') }}" class="neu-btn mt-9 w-full py-4 text-center font-extrabold block" style="color: var(--neu-accent)">
                    <i class="fas fa-mobile-screen-button mr-2"></i>Get the App — Start Earning Free
                </a>
            </div>

            <!-- Artist flow -->
            <div>
                <div class="neu-inset inline-flex items-center space-x-2 px-5 py-2.5 rounded-full text-sm font-extrabold mb-8" style="color: #ec4899">
                    <i class="fas fa-microphone"></i><span>FOR ARTISTS &amp; LABELS</span>
                </div>
                <div class="space-y-8">
                    <div class="neu-step p-7 flex items-start space-x-5">
                        <div class="neu-circle h-12 w-12 shrink-0 text-lg font-extrabold" style="color:#ec4899">1</div>
                        <div>
                            <h3 class="font-extrabold text-lg mb-1" style="color: var(--neu-text-strong)">Submit your track &amp; pick a package</h3>
                            <p class="text-sm font-medium leading-relaxed">Drop your song link, choose your target platforms and decide how many real listens you want. Transparent pricing, no hidden fees.</p>
                        </div>
                    </div>
                    <div class="neu-step p-7 flex items-start space-x-5">
                        <div class="neu-circle h-12 w-12 shrink-0 text-lg font-extrabold" style="color:#ec4899">2</div>
                        <div>
                            <h3 class="font-extrabold text-lg mb-1" style="color: var(--neu-text-strong)">We seed it to vetted listeners</h3>
                            <p class="text-sm font-medium leading-relaxed">Your track is pushed into the feeds of trusted listeners matching your genre — all genuine users, all verified devices.</p>
                        </div>
                    </div>
                    <div class="neu-step p-7 flex items-start space-x-5">
                        <div class="neu-circle h-12 w-12 shrink-0 text-lg font-extrabold" style="color:#ec4899">3</div>
                        <div>
                            <h3 class="font-extrabold text-lg mb-1" style="color: var(--neu-text-strong)">Fraud-checked, real plays</h3>
                            <p class="text-sm font-medium leading-relaxed">Every play passes our fraud engine — listening duration, repeat-listen caps and platform signals are all validated.</p>
                        </div>
                    </div>
                    <div class="neu-step p-7 flex items-start space-x-5">
                        <div class="neu-circle h-12 w-12 shrink-0 text-lg font-extrabold" style="color:#ec4899">4</div>
                        <div>
                            <h3 class="font-extrabold text-lg mb-1" style="color: var(--neu-text-strong)">Watch your numbers climb</h3>
                            <p class="text-sm font-medium leading-relaxed">A live dashboard shows plays, engagement and spend — real-time, transparent analytics before you scale up.</p>
                        </div>
                    </div>
                </div>
                <a href="{{ route('login') }}" class="neu-accent mt-9 w-full py-4 text-center font-extrabold text-white block">
                    <i class="fas fa-rocket mr-2"></i>Promote Your Music
                </a>
            </div>
        </div>
    </section>

    <!-- ═══════════ BANNER ═══════════ -->
    <section class="max-w-6xl mx-auto px-6 pb-24">
        <div class="neu p-4">
            <div class="relative rounded-3xl overflow-hidden">
                <img
                    src="https://images.unsplash.com/photo-1524368535928-5b5e00ddc76b?q=80&w=1600&auto=format&fit=crop"
                    alt="Concert crowd with phones raised"
                    class="w-full object-cover aspect-[21/9]"
                    loading="lazy">
                <div class="absolute inset-0 flex items-center justify-center" style="background: linear-gradient(90deg, rgba(232,237,244,.88) 0%, rgba(232,237,244,.35) 45%, rgba(232,237,244,.88) 100%)">
                    <div class="neu-inset px-8 py-5 rounded-full text-center">
                        <p class="text-lg md:text-2xl font-extrabold" style="color: var(--neu-text-strong)">
                            12,400+ listeners are <span class="text-gradient">earning right now</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════ PLATFORMS ═══════════ -->
    <section id="platforms" class="max-w-6xl mx-auto px-6 pb-24 scroll-mt-8">
        <h2 class="text-3xl md:text-4xl font-extrabold text-center mb-3" style="color: var(--neu-text-strong)">
            Every Platform You Love
        </h2>
        <p class="text-center font-medium max-w-xl mx-auto mb-14">Earn across all the apps already on your phone.</p>

        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-8">
            <div class="neu-step p-8 text-center">
                <div class="neu-circle h-16 w-16 mx-auto mb-5"><i class="fab fa-spotify text-3xl text-[#1DB954]"></i></div>
                <h3 class="font-extrabold text-lg mb-1" style="color: var(--neu-text-strong)">Spotify</h3>
                <p class="text-sm font-medium">Seeded into your Discover Weekly &amp; Release Radar.</p>
            </div>
            <div class="neu-step p-8 text-center">
                <div class="neu-circle h-16 w-16 mx-auto mb-5"><i class="fab fa-youtube text-3xl text-[#FF0000]"></i></div>
                <h3 class="font-extrabold text-lg mb-1" style="color: var(--neu-text-strong)">YouTube</h3>
                <p class="text-sm font-medium">Video plays tracked by real watch-time, not bots.</p>
            </div>
            <div class="neu-step p-8 text-center">
                <div class="neu-circle h-16 w-16 mx-auto mb-5"><i class="fas fa-music text-3xl text-[#FF0033]"></i></div>
                <h3 class="font-extrabold text-lg mb-1" style="color: var(--neu-text-strong)">YouTube Music</h3>
                <p class="text-sm font-medium">Audio streams counted directly on the YT Music app.</p>
            </div>
            <div class="neu-step p-8 text-center">
                <div class="neu-circle h-16 w-16 mx-auto mb-5"><i class="fas fa-waveform text-3xl" style="color: var(--neu-accent)"></i></div>
                <h3 class="font-extrabold text-lg mb-1" style="color: var(--neu-text-strong)">Audiomark</h3>
                <p class="text-sm font-medium">New releases seeded to early-adopter ears.</p>
            </div>
        </div>
        <p class="text-center text-sm font-bold mt-10 opacity-70">
            <i class="fas fa-circle-plus mr-2" style="color: var(--neu-accent)"></i>Apple Music, Deezer, Boomplay &amp; more platforms joining soon.
        </p>
    </section>

    <!-- ═══════════ EARN ═══════════ -->
    <section id="earn" class="max-w-6xl mx-auto px-6 pb-24 scroll-mt-8">
        <div class="neu p-10 md:p-14">
            <div class="grid md:grid-cols-2 gap-10 items-center">
                <div>
                    <h2 class="text-3xl md:text-4xl font-extrabold mb-5" style="color: var(--neu-text-strong)">
                        Turn your <span class="text-gradient">listening habits</span> into income
                    </h2>
                    <p class="font-medium leading-relaxed mb-8">
                        You're already streaming music every day. Why not get paid for it? Streamadollar sits
                        quietly on top of your normal routine — your feed stays yours, your ears stay happy,
                        and your wallet grows.
                    </p>
                    <div class="space-y-4">
                        <div class="neu-inset px-5 py-4 flex items-center justify-between text-sm font-bold">
                            <span><i class="fas fa-check-circle mr-3" style="color: var(--neu-accent)"></i>Per verified play</span>
                            <span class="text-gradient font-extrabold">$0.02 – $0.05</span>
                        </div>
                        <div class="neu-inset px-5 py-4 flex items-center justify-between text-sm font-bold">
                            <span><i class="fas fa-check-circle mr-3" style="color: var(--neu-accent)"></i>Daily listen sessions</span>
                            <span class="text-gradient font-extrabold">20 – 40 tracks</span>
                        </div>
                        <div class="neu-inset px-5 py-4 flex items-center justify-between text-sm font-bold">
                            <span><i class="fas fa-check-circle mr-3" style="color: var(--neu-accent)"></i>Referral bonus</span>
                            <span class="text-gradient font-extrabold">10% lifetime</span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center justify-center">
                    <div class="neu p-8 rounded-full">
                        <div class="neu-circle h-44 w-44 overflow-hidden">
                            <img
                                src="https://images.unsplash.com/photo-1598488035139-bdbb2231ce04?q=80&w=800&auto=format&fit=crop"
                                alt="Woman listening to music on her phone"
                                class="h-full w-full object-cover"
                                loading="lazy">
                        </div>
                    </div>
                    <div class="neu-sm -ml-8 mt-24 px-5 py-4 text-left leading-tight">
                        <p class="text-xs font-bold tracking-widest opacity-60 mb-1">NOW LISTENING</p>
                        <div class="equalizer flex items-end space-x-1 h-4 mb-1">
                            <span></span><span style="animation-delay:.15s"></span><span style="animation-delay:.3s"></span><span style="animation-delay:.1s"></span>
                        </div>
                        <p class="font-extrabold text-sm" style="color: var(--neu-text-strong)">"Neon Dreams"</p>
                        <p class="text-xs font-semibold opacity-60">via Spotify · <span class="text-gradient font-extrabold">$0.04</span> earned</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════ FAQ ═══════════ -->
    <section id="faq" class="max-w-3xl mx-auto px-6 pb-24 scroll-mt-8">
        <h2 class="text-3xl md:text-4xl font-extrabold text-center mb-14" style="color: var(--neu-text-strong)">
            Questions? <span class="text-gradient">Answered.</span>
        </h2>
        <div class="space-y-6">
            <div class="neu-sm px-7 py-5 faq">
                <button class="w-full flex items-center justify-between text-left font-extrabold" style="color: var(--neu-text-strong)" onclick="toggleFaq(this)">
                    Is this really free for listeners?
                    <span class="faq-icon neu-circle h-8 w-8 shrink-0 text-sm"><i class="fas fa-plus"></i></span>
                </button>
                <div class="faq-answer text-sm font-medium leading-relaxed pt-3">100% free. You only need a streaming account you already have. Streamadollar pays you — the artists fund the campaigns.</div>
            </div>
            <div class="neu-sm px-7 py-5 faq">
                <button class="w-full flex items-center justify-between text-left font-extrabold" style="color: var(--neu-text-strong)" onclick="toggleFaq(this)">
                    How are my plays verified?
                    <span class="faq-icon neu-circle h-8 w-8 shrink-0 text-sm"><i class="fas fa-plus"></i></span>
                </button>
                <div class="faq-answer text-sm font-medium leading-relaxed pt-3">We check listening duration, platform activity signals and per-track limits through our fraud engine — if it looks like a bot, it doesn't count (for you or the artist).</div>
            </div>
            <div class="neu-sm px-7 py-5 faq">
                <button class="w-full flex items-center justify-between text-left font-extrabold" style="color: var(--neu-text-strong)" onclick="toggleFaq(this)">
                    How and when do I get paid?
                    <span class="faq-icon neu-circle h-8 w-8 shrink-0 text-sm"><i class="fas fa-plus"></i></span>
                </button>
                <div class="faq-answer text-sm font-medium leading-relaxed pt-3">Verified earnings hit your wallet instantly and you can withdraw anytime via bank transfer, mobile money or crypto. No payout thresholds, no waiting weeks.</div>
            </div>
            <div class="neu-sm px-7 py-5 faq">
                <button class="w-full flex items-center justify-between text-left font-extrabold" style="color: var(--neu-text-strong)" onclick="toggleFaq(this)">
                    Can I earn on multiple platforms?
                    <span class="faq-icon neu-circle h-8 w-8 shrink-0 text-sm"><i class="fas fa-plus"></i></span>
                </button>
                <div class="faq-answer text-sm font-medium leading-relaxed pt-3">Yes — connect Spotify, YouTube, YouTube Music, Audiomark and more. Every verified play on every platform adds up in the same wallet.</div>
            </div>
        </div>
    </section>

    <!-- ═══════════ CTA ═══════════ -->
    <section class="max-w-6xl mx-auto px-6 pb-24">
        <div class="neu p-12 md:p-16 text-center">
            <h2 class="text-3xl md:text-5xl font-extrabold mb-4" style="color: var(--neu-text-strong)">
                Your next listen <span class="text-gradient">pays you.</span>
            </h2>
            <p class="font-medium max-w-xl mx-auto mb-10">Join thousands of listeners earning while they stream — and give artists the real plays they deserve.</p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-5">
                <a href="{{ asset('download/streamadolla-official-with-mutiple-device-v5.apk') }}" class="neu-accent px-10 py-4 font-extrabold text-white">
                    <i class="fas fa-mobile-screen-button mr-2"></i>Download the App
                </a>
                <a href="{{ route('login') }}" class="neu-btn px-10 py-4 font-extrabold" style="color: var(--neu-text-strong)">
                    <i class="fas fa-sign-in-alt mr-2"></i>Artist Login
                </a>
            </div>
        </div>
    </section>

    <!-- ═══════════ TICKER ═══════════ -->
    <div class="pb-16 overflow-hidden">
        <div class="ticker flex items-center space-x-12 text-sm font-bold opacity-50">
            <span>🎧 NEW PLAYS VERIFIED</span><span class="text-gradient font-extrabold">+$124.60</span>
            <span>🎧 "MIDNIGHT DRIVE" SEEDED TO 350 FEEDS</span><span class="text-gradient font-extrabold">+$89.10</span>
            <span>🎧 1,204 PLAYS DELIVERED TODAY</span><span class="text-gradient font-extrabold">+$210.05</span>
            <span>🎧 PAYOUT SENT TO LISTENER #8421</span><span class="text-gradient font-extrabold">$50.00</span>
            <span>🎧 NEW PLAYS VERIFIED</span><span class="text-gradient font-extrabold">+$124.60</span>
            <span>🎧 "MIDNIGHT DRIVE" SEEDED TO 350 FEEDS</span><span class="text-gradient font-extrabold">+$89.10</span>
            <span>🎧 1,204 PLAYS DELIVERED TODAY</span><span class="text-gradient font-extrabold">+$210.05</span>
            <span>🎧 PAYOUT SENT TO LISTENER #8421</span><span class="text-gradient font-extrabold">$50.00</span>
        </div>
    </div>

    <!-- ═══════════ FOOTER ═══════════ -->
    <footer class="border-t border-black/5">
        <div class="max-w-6xl mx-auto px-6 py-10 flex flex-col md:flex-row items-center justify-between text-sm font-semibold opacity-70">
            <div class="flex items-center space-x-3 mb-4 md:mb-0">
                <div class="neu-circle h-9 w-9"><i class="fas fa-music" style="color: var(--neu-accent)"></i></div>
                <span>&copy; {{ date('Y') }} Streamadollar. Listen. Earn. Repeat.</span>
            </div>
            <div class="order-last md:order-none w-full md:w-auto text-center text-xs opacity-50 mb-4 md:mb-0">
                Photos by <a href="https://unsplash.com" target="_blank" rel="noopener" class="underline hover:opacity-100">Unsplash</a> (free license)
            </div>
            <div class="flex items-center space-x-6">
                <a href="{{ asset('download/streamadolla-official-with-mutiple-device-v5.apk') }}" class="hover:opacity-100 transition"><i class="fas fa-mobile-screen-button mr-1"></i>Get the App</a>
                <a href="{{ route('artist.signup') }}" class="hover:opacity-100 transition">Artist Signup</a>
            </div>
        </div>
    </footer>

    <script>
        function toggleFaq(btn) {
            const item = btn.closest('.faq');
            item.classList.toggle('faq-open');
        }
    </script>
</body>

</html>