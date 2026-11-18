<?php
declare(strict_types=1);

$destination = 'https://kaki66.com';
$slides = [
    ['file' => 'member-banner.png', 'alt' => 'KAKI66 মেম্বার ডে ও বিশেষ রিওয়ার্ডের পোস্টার'],
    ['file' => 'member-steps.png', 'alt' => 'KAKI66 মেম্বার ডে-তে রেজিস্ট্রেশন, লগইন ও বোনাস পাওয়ার ধাপ'],
    ['file' => 'platform-poster.png', 'alt' => 'KAKI66 গেম, সুবিধা ও সাপোর্টের প্রচারণামূলক পোস্টার'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['action'] ?? '') === 'track') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => true, 'event' => 'kaki66_cta_click'], JSON_UNESCAPED_UNICODE);
    exit;
}

$url = static fn(string $file): string => 'assets/' . rawurlencode($file);
$safeDestination = htmlspecialchars($destination, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="bn">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>KAKI66 — খেলুন, জিতুন, আনন্দ করুন</title>
  <meta name="description" content="KAKI66-এর অনলাইন গেমিং প্ল্যাটফর্মে খেলুন, মেম্বার রিওয়ার্ড পান এবং দ্রুত সাপোর্ট নিন। ১৮ বছরের বেশি বয়সীদের জন্য।">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Noto+Sans+Bengali:wght@400;500;600;700;800;900&display=swap');
    :root{--bg:#031b11;--panel:#092819;--green:#0b3a20;--gold:#f2cc38;--cream:#fff3b0;--muted:#aec5ac;--line:rgba(226,190,57,.34);--font:'Noto Sans Bengali','DM Sans',Arial,sans-serif}
    *{box-sizing:border-box}html{scroll-behavior:smooth}body{margin:0;background:var(--bg);color:var(--cream);font:16px/1.7 var(--font)}
    a{color:inherit}.wrap{width:min(1180px,calc(100% - 32px));margin:auto}.nav{position:relative;z-index:4;border-bottom:1px solid rgba(217,183,47,.2);background:rgba(3,27,17,.82);backdrop-filter:blur(16px)}.nav-inner{min-height:74px;display:flex;align-items:center;justify-content:space-between;gap:20px}.brand{display:flex;align-items:center;gap:10px;text-decoration:none}.mark{width:40px;height:40px;display:grid;place-items:center;border:1px solid var(--gold);border-radius:50%;color:var(--gold);font-size:20px}.brand strong{font-size:30px;line-height:1;color:#f8df68}.brand strong span{color:#e9a923}.links{display:flex;gap:28px;color:#b7cdb4;font-size:12px;font-weight:700}.links a{text-decoration:none}.links a:hover{color:var(--gold)}.btn{display:inline-flex;align-items:center;justify-content:center;gap:9px;border:0;border-radius:999px;padding:12px 22px;background:var(--gold);color:#082616;font-weight:900;text-decoration:none;cursor:pointer;box-shadow:0 5px 0 #9b6713,0 14px 28px rgba(232,188,30,.14);transition:.25s}.btn:hover,.btn:focus-visible{transform:translateY(-3px);background:#ffe889}.btn.small{padding:9px 16px;font-size:13px}

    /* Top campaign slider */
    .slider{position:relative;isolation:isolate;overflow:hidden;min-height:clamp(330px,48vw,590px);border-bottom:1px solid rgba(226,190,57,.35);background:#061f13}.slide{position:absolute;inset:0;display:grid;align-items:end;opacity:0;visibility:hidden;transform:scale(1.02);transition:opacity .7s ease,transform 6s ease,visibility .7s}.slide.active{opacity:1;visibility:visible;transform:scale(1)}.slide img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;object-position:center;z-index:-2}.slide:after{content:'';position:absolute;inset:0;z-index:-1;background:linear-gradient(90deg,rgba(3,27,17,.9) 0%,rgba(3,27,17,.48) 37%,rgba(3,27,17,.05) 75%),linear-gradient(0deg,rgba(3,27,17,.72),transparent 48%)}.slide-copy{width:min(1180px,calc(100% - 32px));margin:0 auto;padding:45px 0 52px}.slide-copy span{display:inline-block;margin-bottom:10px;padding:5px 12px;border:1px solid rgba(242,204,56,.6);border-radius:999px;background:rgba(3,27,17,.6);color:var(--gold);font-size:12px;font-weight:800}.slide-copy h1{max-width:580px;margin:0;color:var(--cream);font-size:clamp(30px,5vw,66px);line-height:1.15}.slide-copy p{max-width:500px;margin:12px 0 22px;color:#e2e9ce;font-size:17px}.slider-arrow{position:absolute;top:50%;z-index:2;width:44px;height:44px;border:1px solid rgba(242,204,56,.65);border-radius:50%;background:rgba(3,27,17,.68);color:var(--gold);font-size:24px;cursor:pointer;transform:translateY(-50%)}.slider-arrow:hover{background:var(--gold);color:var(--bg)}.prev{left:18px}.next{right:18px}.dots{position:absolute;right:0;bottom:18px;left:0;z-index:2;display:flex;justify-content:center;gap:8px}.dot{width:28px;height:4px;border:0;border-radius:4px;background:rgba(255,243,176,.45);cursor:pointer}.dot.active{background:var(--gold)}

    main{background:radial-gradient(circle at 85% 15%,rgba(20,154,81,.18),transparent 28rem),var(--bg)}section{padding:78px 0}.intro{display:grid;grid-template-columns:.8fr 1.2fr;align-items:end;gap:48px}.eyebrow{margin-bottom:12px;color:#e8c63d;font-size:12px;font-weight:800;letter-spacing:.08em}.intro h2,.campaign h2,.games h2{margin:0;font-size:clamp(40px,6vw,78px);line-height:1.05}.intro h2 span,.campaign h2 span,.games h2 span{color:#f0c936}.copy{margin:0;color:var(--muted);font-size:18px}.features{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-top:42px}.feature{min-height:190px;padding:22px;border:1px solid rgba(203,169,44,.25);border-radius:18px;background:var(--panel);transition:.25s}.feature:hover{transform:translateY(-5px);border-color:rgba(241,207,73,.7)}.feature-no{display:flex;justify-content:space-between;color:var(--gold);font-size:14px}.feature h3{margin:42px 0 4px;color:#f7e58b;font-size:22px}.feature p{margin:0;color:#99b39a;font-size:14px}.campaign{display:grid;grid-template-columns:1fr 1fr;align-items:center;overflow:hidden;border:1px solid var(--line);border-radius:26px;background:var(--green)}.campaign-copy{padding:48px}.campaign-copy p{max-width:440px;color:var(--muted);font-size:17px}.campaign-art{padding:18px}.campaign-art img,.game-art img{display:block;width:100%;border-radius:18px}.games{border-top:1px solid var(--line);border-bottom:1px solid var(--line);background:#062116}.games-grid{display:grid;grid-template-columns:1.1fr .9fr;align-items:center;gap:46px}.game-art{overflow:hidden;border:1px solid var(--line);border-radius:24px}.games p{color:var(--muted);font-size:17px}.tags{display:flex;flex-wrap:wrap;gap:8px;margin:24px 0 30px}.tag{padding:7px 13px;border:1px solid rgba(217,183,55,.35);border-radius:999px;color:#e8df9f;font-size:12px}.footer{padding:45px 0 25px}.footer-row{display:flex;align-items:end;justify-content:space-between;gap:22px;padding-bottom:30px;border-bottom:1px solid var(--line)}.footer-logo{margin:0;color:#f8df68;font-size:46px;font-weight:900;line-height:1}.footer-logo span{color:#e9a923}.footer-note{margin:8px 0 0;color:#8fa992}.legal{display:flex;justify-content:space-between;gap:20px;padding-top:20px;color:#829c86;font-size:12px}.age{display:inline-block;margin-right:7px;padding:0 5px;border:1px solid var(--gold);border-radius:3px;color:var(--gold);font-weight:900}
    @media(max-width:850px){.links{display:none}.slider{min-height:540px}.slide img{object-position:center}.slide:after{background:linear-gradient(0deg,rgba(3,27,17,.9) 0%,rgba(3,27,17,.18) 70%)}.slide-copy{width:100%;padding:26px 28px 78px;background:linear-gradient(0deg,rgba(3,27,17,.98) 0%,rgba(3,27,17,.82) 62%,transparent 100%)}.slide-copy h1{max-width:480px}.intro,.campaign,.games-grid{grid-template-columns:1fr}.features{grid-template-columns:repeat(2,1fr)}.campaign-copy{padding:34px}.games-copy{order:-1}}
    @media(max-width:560px){.wrap{width:min(100% - 24px,1180px)}.nav-inner{min-height:66px}.brand strong{font-size:26px}.slider{min-height:540px}.slide img{object-position:58% center}.slide-copy{padding:22px 24px 78px}.slide-copy span{font-size:11px}.slide-copy h1{max-width:330px;font-size:34px}.slide-copy p{font-size:14px;line-height:1.6}.slider-arrow{top:auto;bottom:20px;transform:none}.prev{left:14px}.next{right:14px}.dots{bottom:38px}.features{grid-template-columns:1fr}.btn{width:100%}section{padding:56px 0}.footer-row,.legal{align-items:flex-start;flex-direction:column}.footer-logo{font-size:38px}}
    @media(prefers-reduced-motion:reduce){*,*::before,*::after{scroll-behavior:auto!important;transition-duration:.01ms!important}}
  </style>
</head>
<body>
  <header class="nav">
    <div class="wrap nav-inner">
      <a class="brand" href="#top" aria-label="KAKI66 হোম"><span class="mark">♛</span><strong>KAKI<span>66</span></strong></a>
      <nav class="links" aria-label="প্রধান নেভিগেশন"><a href="#reasons">কেন KAKI66</a><a href="#member-day">মেম্বার ডে</a><a href="#games">গেমসমূহ</a></nav>
      <a class="btn small" href="<?= $safeDestination ?>" data-cta>এখনই খেলুন ↗</a>
    </div>
  </header>

  <section class="slider" aria-label="KAKI66 প্রচারণার ছবি">
    <?php foreach ($slides as $index => $slide): ?>
      <article class="slide<?= $index === 0 ? ' active' : '' ?>" data-slide="<?= $index ?>">
        <img src="<?= $url($slide['file']) ?>" alt="<?= htmlspecialchars($slide['alt'], ENT_QUOTES, 'UTF-8') ?>">
        <div class="slide-copy">
          <span><?= $index === 1 ? 'বিশেষ মেম্বার ডে' : 'KAKI66-এর নতুন অভিজ্ঞতা' ?></span>
          <h1><?= $index === 0 ? 'পছন্দের গেম, দারুণ আনন্দ' : ($index === 1 ? 'মেম্বার ডে বোনাস নিন' : 'খেলুন, জিতুন, আনন্দ করুন') ?></h1>
          <p><?= $index === 1 ? 'রেজিস্ট্রেশন করুন, লগইন করুন এবং নিয়মিত রিওয়ার্ডের সুযোগ নিন।' : 'আপনার পছন্দের গেম ও রিওয়ার্ড এখন এক প্ল্যাটফর্মেই।' ?></p>
          <a class="btn" href="<?= $safeDestination ?>" data-cta>এখনই রেজিস্টার করুন ↗</a>
        </div>
      </article>
    <?php endforeach; ?>
    <button class="slider-arrow prev" type="button" aria-label="আগের ছবি">‹</button>
    <button class="slider-arrow next" type="button" aria-label="পরের ছবি">›</button>
    <div class="dots" role="tablist" aria-label="স্লাইড বাছাই করুন">
      <?php foreach ($slides as $index => $_): ?><button class="dot<?= $index === 0 ? ' active' : '' ?>" type="button" data-dot="<?= $index ?>" aria-label="স্লাইড <?= $index + 1 ?>"></button><?php endforeach; ?>
    </div>
  </section>

  <main id="top">
    <section id="content" class="wrap">
      <div class="intro">
        <div><div class="eyebrow">KAKI66-এর বিশেষত্ব</div><h2>আনন্দের<br><span>আরও অনেক</span><br>উপায়।</h2></div>
        <p class="copy">দারুণ একটি রাতের আনন্দ এখন এক জায়গায়। পছন্দের গেম বেছে নিন, নিজের গতিতে খেলুন এবং প্রয়োজন হলেই সাপোর্ট টিমের সাহায্য নিন।</p>
      </div>
      <div id="reasons" class="features">
        <article class="feature"><div class="feature-no"><span>০১</span><span>✦</span></div><h3>দ্রুত শুরু করুন</h3><p>অপেক্ষা কম, খেলার সময় বেশি।</p></article>
        <article class="feature"><div class="feature-no"><span>০২</span><span>✦</span></div><h3>নিরাপদ পরিবেশ</h3><p>স্বস্তিতে খেলার জন্য যত্নসহকারে তৈরি।</p></article>
        <article class="feature"><div class="feature-no"><span>০৩</span><span>✦</span></div><h3>মেম্বার রিওয়ার্ড</h3><p>নিয়মিত খেলোয়াড়দের জন্য বাড়তি সুবিধা।</p></article>
        <article class="feature"><div class="feature-no"><span>০৪</span><span>✦</span></div><h3>সবসময় পাশে</h3><p>দিন-রাত যেকোনো সময় সহায়তা।</p></article>
      </div>
    </section>

    <section id="member-day" class="wrap">
      <div class="campaign"><div class="campaign-copy"><div class="eyebrow">যে অফারে বারবার ফিরতে ইচ্ছে করবে</div><h2>আপনার দিন।<br><span>আপনার বোনাস।</span></h2><p>রেজিস্ট্রেশন করুন, লগইন করুন, মেম্বার ডে বোনাস নিন এবং খেলার আনন্দ চালিয়ে যান।</p><a class="btn" href="<?= $safeDestination ?>" data-cta>আপনার বোনাস নিন ↗</a></div><div class="campaign-art"><img src="<?= $url('member-steps.png') ?>" alt="KAKI66 মেম্বার ডে-তে রেজিস্ট্রেশন, লগইন, বোনাস ও খেলার ধাপ"></div></div>
    </section>

    <section id="games" class="games"><div class="wrap games-grid"><div class="game-art"><img src="<?= $url('member-banner.png') ?>" alt="KAKI66 মেম্বার ডে ও বিশেষ রিওয়ার্ডের পোস্টার"></div><div class="games-copy"><div class="eyebrow">প্রতি মাসে আরও কিছু আনন্দ</div><h2>পছন্দের<br><span>গেম বেছে নিন।</span></h2><p>স্লট, লাইভ ক্যাসিনো, স্পোর্টস, লটারি ও ফিশিং গেম—আপনার পছন্দের গেম খুঁজে নিন এক প্ল্যাটফর্মেই।</p><div class="tags"><span class="tag">স্লট</span><span class="tag">লাইভ ক্যাসিনো</span><span class="tag">স্পোর্টস</span><span class="tag">লটারি</span><span class="tag">ফিশিং</span></div><a class="btn" href="<?= $safeDestination ?>" data-cta>গেমসমূহ দেখুন ↗</a></div></div></section>
  </main>

  <footer class="wrap footer"><div class="footer-row"><div><p class="footer-logo">KAKI<span>66</span><small>.COM</small></p><p class="footer-note">সচেতনভাবে খেলুন। আনন্দ বজায় রাখুন। নিজের সীমা জানুন।</p></div><a class="btn" href="<?= $safeDestination ?>" data-cta>kaki66.com ভিজিট করুন ↗</a></div><div class="legal"><p><span class="age">১৮+</span> শুধুমাত্র প্রাপ্তবয়স্কদের জন্য। দায়িত্বশীলভাবে খেলুন। জুয়া আসক্তি তৈরি করতে পারে—সীমা নির্ধারণ করুন এবং ক্ষতি পুষিয়ে নিতে আবার খেলবেন না।</p><p>© KAKI66 · দায়িত্বশীলভাবে খেলুন</p></div></footer>

  <script>
    (() => {
      const slides = [...document.querySelectorAll('[data-slide]')];
      const dots = [...document.querySelectorAll('[data-dot]')];
      let active = 0;
      let timer;
      const show = (index) => {
        active = (index + slides.length) % slides.length;
        slides.forEach((slide, i) => slide.classList.toggle('active', i === active));
        dots.forEach((dot, i) => dot.classList.toggle('active', i === active));
      };
      const restart = () => { clearInterval(timer); timer = setInterval(() => show(active + 1), 5000); };
      document.querySelector('.prev').addEventListener('click', () => { show(active - 1); restart(); });
      document.querySelector('.next').addEventListener('click', () => { show(active + 1); restart(); });
      dots.forEach((dot, i) => dot.addEventListener('click', () => { show(i); restart(); }));
      restart();
      document.querySelectorAll('[data-cta]').forEach((link) => link.addEventListener('click', (event) => {
        event.preventDefault();
        try { void fetch('?action=track', {method:'POST', keepalive:true, headers:{'Content-Type':'application/json'}, body:JSON.stringify({event:'kaki66_cta_click'})}).catch(() => undefined); } catch (_) {}
        window.location.assign(<?= json_encode($destination, JSON_UNESCAPED_SLASHES) ?>);
      }));
    })();
  </script>
</body>
</html>