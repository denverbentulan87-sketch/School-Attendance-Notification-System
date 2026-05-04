<?php
include 'includes/db.php';
$result = $conn->query("SELECT * FROM users");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>SANS — School Attendance & Notification System</title>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,600;0,700;0,900;1,600&family=Figtree:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --navy:    #0D1B2A;
      --navy-2:  #162236;
      --navy-3:  #1E3050;
      --slate:   #4A6FA5;
      --ice:     #E8F0FB;
      --gold:    #C8973A;
      --gold-lt: #E8B860;
      --white:   #FFFFFF;
      --gray-1:  #F4F6FA;
      --gray-2:  #E2E8F0;
      --gray-3:  #94A3B8;
      --gray-4:  #64748B;
      --text:    #1A2332;
      --danger:  #DC2626;
      --success: #16A34A;
      --radius:  14px;
    }

    html { scroll-behavior: smooth; font-family: 'Figtree', sans-serif; }
    body { background: var(--navy); color: var(--white); overflow-x: hidden; }

    /* ── NAV ── */
    .lp-nav {
      position: fixed;
      top: 0; left: 0; right: 0;
      z-index: 999;
      padding: 18px 60px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      background: rgba(13,27,42,0.85);
      backdrop-filter: blur(16px);
      border-bottom: 1px solid rgba(255,255,255,0.06);
      transition: all 0.3s;
    }

    .lp-nav-brand {
      display: flex;
      align-items: center;
      gap: 12px;
      text-decoration: none;
    }

    .lp-nav-icon {
      width: 40px; height: 40px;
      background: linear-gradient(135deg, var(--gold), var(--gold-lt));
      border-radius: 11px;
      display: flex; align-items: center; justify-content: center;
      font-size: 20px;
      box-shadow: 0 4px 14px rgba(200,151,58,0.35);
    }

    .lp-nav-name {
      font-family: 'Fraunces', serif;
      font-size: 17px;
      font-weight: 700;
      color: var(--white);
      letter-spacing: -0.3px;
      line-height: 1.1;
    }

    .lp-nav-tag {
      font-size: 10px;
      color: var(--gold);
      font-weight: 600;
      letter-spacing: 1.2px;
      text-transform: uppercase;
    }

    .lp-nav-links {
      display: flex;
      align-items: center;
      gap: 32px;
      list-style: none;
    }

    .lp-nav-links a {
      font-size: 14px;
      font-weight: 500;
      color: var(--gray-3);
      text-decoration: none;
      transition: color 0.2s;
    }

    .lp-nav-links a:hover { color: var(--white); }

    .lp-nav-cta {
      background: linear-gradient(135deg, var(--gold), var(--gold-lt));
      color: var(--navy) !important;
      font-weight: 700 !important;
      padding: 10px 22px;
      border-radius: 9px;
      font-size: 13.5px !important;
      box-shadow: 0 4px 14px rgba(200,151,58,0.3);
      transition: transform 0.2s, box-shadow 0.2s !important;
    }

    .lp-nav-cta:hover {
      transform: translateY(-1px);
      box-shadow: 0 6px 20px rgba(200,151,58,0.45) !important;
      color: var(--navy) !important;
    }

    /* ── HERO ── */
    .lp-hero {
      min-height: 100vh;
      display: flex;
      align-items: center;
      position: relative;
      overflow: hidden;
      padding: 120px 60px 80px;
    }

    .lp-hero::before {
      content: '';
      position: absolute; inset: 0;
      background-image:
        linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
      background-size: 72px 72px;
      pointer-events: none;
    }

    .hero-blob-1 {
      position: absolute;
      top: -160px; right: -100px;
      width: 600px; height: 600px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(200,151,58,0.14) 0%, transparent 65%);
      pointer-events: none;
    }

    .hero-blob-2 {
      position: absolute;
      bottom: -120px; left: -80px;
      width: 480px; height: 480px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(74,111,165,0.16) 0%, transparent 65%);
      pointer-events: none;
    }

    .hero-blob-3 {
      position: absolute;
      top: 40%; left: 50%;
      transform: translate(-50%, -50%);
      width: 700px; height: 300px;
      background: radial-gradient(ellipse, rgba(200,151,58,0.05) 0%, transparent 70%);
      pointer-events: none;
    }

    .lp-hero-inner {
      position: relative;
      z-index: 2;
      max-width: 1200px;
      margin: 0 auto;
      width: 100%;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 80px;
      align-items: center;
    }

    .hero-eyebrow {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: rgba(200,151,58,0.12);
      border: 1px solid rgba(200,151,58,0.25);
      border-radius: 100px;
      padding: 6px 16px;
      font-size: 12px;
      font-weight: 600;
      color: var(--gold-lt);
      letter-spacing: 0.8px;
      text-transform: uppercase;
      margin-bottom: 24px;
      animation: fadeUp 0.7s ease both;
    }

    .hero-eyebrow span { font-size: 14px; }

    .hero-h1 {
      font-family: 'Fraunces', serif;
      font-size: clamp(42px, 5vw, 68px);
      font-weight: 900;
      line-height: 1.02;
      letter-spacing: -2px;
      color: var(--white);
      margin-bottom: 24px;
      animation: fadeUp 0.7s 0.1s ease both;
    }

    .hero-h1 .gold-italic { color: var(--gold); font-style: italic; }

    .hero-desc {
      font-size: 16px;
      line-height: 1.75;
      color: var(--gray-3);
      max-width: 460px;
      margin-bottom: 40px;
      animation: fadeUp 0.7s 0.2s ease both;
    }

    .hero-actions {
      display: flex;
      gap: 14px;
      flex-wrap: wrap;
      animation: fadeUp 0.7s 0.3s ease both;
    }

    .btn-hero-primary {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: linear-gradient(135deg, var(--gold), var(--gold-lt));
      color: var(--navy);
      font-weight: 700;
      font-size: 15px;
      padding: 14px 28px;
      border-radius: 11px;
      text-decoration: none;
      box-shadow: 0 6px 24px rgba(200,151,58,0.35);
      transition: transform 0.2s, box-shadow 0.2s;
      font-family: 'Figtree', sans-serif;
    }

    .btn-hero-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 32px rgba(200,151,58,0.5);
    }

    .btn-hero-secondary {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: rgba(255,255,255,0.06);
      border: 1px solid rgba(255,255,255,0.12);
      color: var(--white);
      font-weight: 600;
      font-size: 15px;
      padding: 14px 28px;
      border-radius: 11px;
      text-decoration: none;
      transition: background 0.2s, border 0.2s;
      font-family: 'Figtree', sans-serif;
    }

    .btn-hero-secondary:hover {
      background: rgba(255,255,255,0.1);
      border-color: rgba(255,255,255,0.2);
    }

    .hero-right { animation: fadeUp 0.7s 0.25s ease both; }

    .hero-stats-card {
      background: rgba(22,34,54,0.7);
      border: 1px solid rgba(255,255,255,0.08);
      border-radius: 20px;
      padding: 32px;
      backdrop-filter: blur(16px);
      box-shadow: 0 24px 64px rgba(0,0,0,0.3);
    }

    .hsc-label { font-size: 11px; font-weight: 700; color: var(--gold); letter-spacing: 1.5px; text-transform: uppercase; margin-bottom: 20px; }
    .hsc-stats { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px; }
    .hsc-stat { background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.06); border-radius: 12px; padding: 18px 20px; }
    .hsc-stat-num { font-family: 'Fraunces', serif; font-size: 30px; font-weight: 700; color: var(--gold-lt); letter-spacing: -1px; line-height: 1; }
    .hsc-stat-label { font-size: 11.5px; color: var(--gray-3); font-weight: 500; margin-top: 5px; text-transform: uppercase; letter-spacing: 0.6px; }
    .hsc-divider { height: 1px; background: rgba(255,255,255,0.06); margin-bottom: 20px; }
    .hsc-features { display: flex; flex-direction: column; gap: 12px; }
    .hsc-feature { display: flex; align-items: center; gap: 10px; font-size: 13.5px; color: var(--gray-3); font-weight: 500; }
    .hsc-feature-dot { width: 6px; height: 6px; border-radius: 50%; background: var(--gold); flex-shrink: 0; box-shadow: 0 0 6px rgba(200,151,58,0.5); }

    /* ── FEATURES SECTION ── */
    .lp-features { padding: 100px 60px; background: var(--navy-2); position: relative; overflow: hidden; }
    .lp-features::before { content: ''; position: absolute; inset: 0; background-image: linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px); background-size: 60px 60px; pointer-events: none; }

    .section-inner { max-width: 1200px; margin: 0 auto; position: relative; z-index: 2; }

    .section-eyebrow { display: inline-flex; align-items: center; gap: 8px; background: rgba(200,151,58,0.1); border: 1px solid rgba(200,151,58,0.2); border-radius: 100px; padding: 5px 14px; font-size: 11.5px; font-weight: 700; color: var(--gold-lt); letter-spacing: 1px; text-transform: uppercase; margin-bottom: 16px; }
    .section-title { font-family: 'Fraunces', serif; font-size: clamp(30px, 3.5vw, 44px); font-weight: 800; color: var(--white); letter-spacing: -1.2px; line-height: 1.1; margin-bottom: 16px; }
    .section-sub { font-size: 15px; color: var(--gray-3); max-width: 500px; line-height: 1.7; margin-bottom: 56px; }

    .features-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
    .feature-card { background: rgba(13,27,42,0.6); border: 1px solid rgba(255,255,255,0.07); border-radius: 16px; padding: 28px 26px; transition: transform 0.25s, border-color 0.25s, box-shadow 0.25s; position: relative; overflow: hidden; }
    .feature-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px; background: linear-gradient(90deg, var(--gold), transparent); opacity: 0; transition: opacity 0.25s; }
    .feature-card:hover { transform: translateY(-4px); border-color: rgba(200,151,58,0.2); box-shadow: 0 16px 40px rgba(0,0,0,0.25); }
    .feature-card:hover::before { opacity: 1; }
    .feature-icon { width: 48px; height: 48px; background: rgba(200,151,58,0.12); border: 1px solid rgba(200,151,58,0.2); border-radius: 13px; display: flex; align-items: center; justify-content: center; font-size: 22px; margin-bottom: 18px; }
    .feature-title { font-family: 'Fraunces', serif; font-size: 18px; font-weight: 700; color: var(--white); margin-bottom: 10px; letter-spacing: -0.3px; }
    .feature-desc { font-size: 13.5px; color: var(--gray-3); line-height: 1.65; }

    /* ── HOW IT WORKS ── */
    .lp-how { padding: 100px 60px; background: var(--navy); position: relative; overflow: hidden; }
    .lp-how::after { content: ''; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 800px; height: 400px; background: radial-gradient(ellipse, rgba(74,111,165,0.08) 0%, transparent 70%); pointer-events: none; }
    .steps-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0; position: relative; }
    .steps-grid::before { content: ''; position: absolute; top: 28px; left: calc(12.5% + 24px); right: calc(12.5% + 24px); height: 1px; background: linear-gradient(90deg, var(--gold), rgba(200,151,58,0.2), var(--gold)); z-index: 0; }
    .step-item { display: flex; flex-direction: column; align-items: center; text-align: center; padding: 0 20px; position: relative; z-index: 1; }
    .step-num { width: 56px; height: 56px; border-radius: 50%; background: linear-gradient(135deg, var(--gold), var(--gold-lt)); display: flex; align-items: center; justify-content: center; font-family: 'Fraunces', serif; font-size: 20px; font-weight: 800; color: var(--navy); margin-bottom: 20px; box-shadow: 0 6px 20px rgba(200,151,58,0.35); flex-shrink: 0; }
    .step-title { font-family: 'Fraunces', serif; font-size: 17px; font-weight: 700; color: var(--white); margin-bottom: 10px; letter-spacing: -0.3px; }
    .step-desc { font-size: 13px; color: var(--gray-3); line-height: 1.65; }

    /* ── ROLES SECTION ── */
    .lp-roles { padding: 100px 60px; background: var(--navy-2); position: relative; }
    .roles-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-top: 56px; }
    .role-card { border-radius: 18px; padding: 32px 28px; border: 1px solid rgba(255,255,255,0.07); transition: transform 0.25s, box-shadow 0.25s; position: relative; overflow: hidden; }
    .role-card:hover { transform: translateY(-4px); box-shadow: 0 20px 48px rgba(0,0,0,0.3); }
    .role-card.admin-card { background: linear-gradient(135deg, #0d1f35, #0f2a1e); }
    .role-card.student-card { background: linear-gradient(135deg, #14213d, #0d1b2a); }
    .role-card.parent-card { background: linear-gradient(135deg, #1a1a2e, #0d1b2a); }
    .role-badge { display: inline-flex; align-items: center; gap: 8px; font-size: 11px; font-weight: 700; letter-spacing: 1.2px; text-transform: uppercase; padding: 5px 12px; border-radius: 100px; margin-bottom: 20px; }
    .admin-card .role-badge { background: rgba(22,163,74,0.15); color: #4ade80; border: 1px solid rgba(74,222,128,0.2); }
    .student-card .role-badge { background: rgba(74,111,165,0.2); color: #93C5FD; border: 1px solid rgba(147,197,253,0.2); }
    .parent-card .role-badge { background: rgba(200,151,58,0.15); color: var(--gold-lt); border: 1px solid rgba(232,184,96,0.2); }
    .role-title { font-family: 'Fraunces', serif; font-size: 22px; font-weight: 800; color: var(--white); margin-bottom: 14px; letter-spacing: -0.5px; }
    .role-desc { font-size: 13.5px; color: var(--gray-3); line-height: 1.65; margin-bottom: 20px; }
    .role-perks { list-style: none; display: flex; flex-direction: column; gap: 8px; }
    .role-perks li { display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--gray-3); }
    .role-perks li::before { content: '✓'; font-size: 11px; font-weight: 800; color: var(--gold); flex-shrink: 0; }

    /* ── CTA BANNER ── */
    .lp-cta { padding: 100px 60px; background: var(--navy); position: relative; overflow: hidden; }
    .lp-cta::before { content: ''; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 700px; height: 400px; background: radial-gradient(ellipse, rgba(200,151,58,0.1) 0%, transparent 65%); pointer-events: none; }
    .cta-box { max-width: 700px; margin: 0 auto; text-align: center; position: relative; z-index: 2; }
    .cta-box .section-title { margin-bottom: 16px; }
    .cta-box .section-sub { margin: 0 auto 40px; text-align: center; }
    .cta-actions { display: flex; gap: 14px; justify-content: center; flex-wrap: wrap; }

    /* ── FOOTER ── */
    .lp-footer { background: rgba(13,27,42,0.95); border-top: 1px solid rgba(255,255,255,0.06); padding: 28px 60px; display: flex; align-items: center; justify-content: space-between; }
    .footer-brand { display: flex; align-items: center; gap: 10px; text-decoration: none; }
    .footer-brand-icon { width: 32px; height: 32px; background: linear-gradient(135deg, var(--gold), var(--gold-lt)); border-radius: 9px; display: flex; align-items: center; justify-content: center; font-size: 16px; }
    .footer-brand-name { font-family: 'Fraunces', serif; font-size: 15px; font-weight: 700; color: var(--white); }
    .footer-copy { font-size: 12.5px; color: var(--gray-4); }
    .footer-links { display: flex; gap: 24px; list-style: none; }
    .footer-links a { font-size: 13px; color: var(--gray-4); text-decoration: none; transition: color 0.2s; }
    .footer-links a:hover { color: var(--gray-3); }

    /* ══════════════════════════════════════
       AUTH SECTION — centered, no left panel
    ══════════════════════════════════════ */
    .auth-section {
      background: var(--navy);
      min-height: 100vh;
      position: relative;
      overflow: hidden;
    }

    .auth-section::before {
      content: '';
      position: absolute;
      top: 50%; left: 50%;
      transform: translate(-50%, -50%);
      width: 700px; height: 500px;
      background: radial-gradient(ellipse, rgba(200,151,58,0.08) 0%, transparent 65%);
      pointer-events: none;
    }

    .page {
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      padding: 40px 24px;
      position: relative;
      z-index: 2;
    }

    .card {
      width: 100%;
      max-width: 440px;
      background: var(--white);
      border-radius: 20px;
      padding: 40px 40px;
      box-shadow: 0 24px 64px rgba(0,0,0,0.35);
      animation: fadeUp 0.6s ease both;
    }

    .tab-row {
      display: flex; background: var(--gray-1);
      border-radius: 12px; padding: 5px;
      margin-bottom: 36px; gap: 4px;
    }

    .tab-btn {
      flex: 1; padding: 11px 16px;
      border: none; border-radius: 9px;
      font-family: 'Figtree', sans-serif;
      font-size: 14px; font-weight: 600;
      cursor: pointer; background: transparent;
      color: var(--gray-3); transition: all 0.2s ease;
      letter-spacing: -0.1px;
    }

    .tab-btn.active {
      background: var(--white); color: var(--navy);
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }

    .form-view { display: none; }
    .form-view.active { display: block; }

    .form-title { font-family: 'Fraunces', serif; font-size: 28px; font-weight: 700; color: var(--navy); letter-spacing: -0.8px; margin-bottom: 6px; line-height: 1.15; }
    .form-sub { font-size: 14px; color: var(--gray-3); margin-bottom: 28px; font-weight: 400; }

    .field-group { margin-bottom: 18px; }
    .field-label { display: block; font-size: 11.5px; font-weight: 700; color: var(--gray-4); text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 8px; }
    .field-wrap { position: relative; }
    .field-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); font-size: 15px; pointer-events: none; line-height: 1; }

    .field-wrap input,
    .field-wrap select {
      width: 100%; padding: 13px 14px 13px 42px;
      border: 1.5px solid var(--gray-2); border-radius: 10px;
      font-size: 14px; font-family: 'Figtree', sans-serif;
      font-weight: 500; color: var(--navy);
      background: var(--gray-1); outline: none;
      transition: all 0.2s ease; appearance: none;
    }

    .field-wrap input:focus,
    .field-wrap select:focus {
      border-color: var(--slate); background: var(--white);
      box-shadow: 0 0 0 3px rgba(74,111,165,0.12);
    }

    .field-wrap input::placeholder { color: var(--gray-3); font-weight: 400; }
    .field-wrap select { background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%2394A3B8' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 14px center; padding-right: 36px; }

    .field-hint { font-size: 11.5px; color: var(--gray-3); margin-top: 6px; padding-left: 2px; font-weight: 400; }
    .field-meta { display: flex; justify-content: flex-end; margin-bottom: 22px; margin-top: -8px; }
    .forgot-link { font-size: 13px; color: var(--slate); text-decoration: none; font-weight: 600; transition: color 0.2s; }
    .forgot-link:hover { color: var(--navy); }

    .btn-submit {
      width: 100%; padding: 14px 20px;
      background: var(--navy); color: var(--white);
      border: none; border-radius: 11px;
      font-size: 15px; font-family: 'Figtree', sans-serif;
      font-weight: 700; cursor: pointer;
      letter-spacing: -0.2px; transition: all 0.2s ease;
      position: relative; overflow: hidden; margin-top: 4px;
    }

    .btn-submit::after { content: ''; position: absolute; inset: 0; background: linear-gradient(135deg, rgba(255,255,255,0.08), transparent); pointer-events: none; }
    .btn-submit:hover { background: var(--navy-3); transform: translateY(-1px); box-shadow: 0 8px 24px rgba(13,27,42,0.25); }
    .btn-submit:active { transform: translateY(0); }

    .alert { padding: 12px 16px; border-radius: 9px; font-size: 13.5px; font-weight: 500; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }
    .alert-error { background: #FEF2F2; color: var(--danger); border: 1px solid #FECACA; }

    .success-splash { display: none; flex-direction: column; align-items: center; text-align: center; padding: 20px 0; }
    .splash-icon-wrap { width: 72px; height: 72px; background: linear-gradient(135deg, #DCFCE7, #BBF7D0); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px; margin-bottom: 20px; box-shadow: 0 8px 24px rgba(22,163,74,0.2); }
    .splash-title { font-family: 'Fraunces', serif; font-size: 26px; font-weight: 700; color: var(--navy); margin-bottom: 10px; letter-spacing: -0.5px; }
    .splash-sub { font-size: 14px; color: var(--gray-4); line-height: 1.6; margin-bottom: 28px; }
    .splash-back { background: var(--navy); color: var(--white); border: none; padding: 12px 28px; border-radius: 10px; font-size: 14px; font-family: 'Figtree', sans-serif; font-weight: 700; cursor: pointer; transition: all 0.2s; }
    .splash-back:hover { background: var(--navy-3); transform: translateY(-1px); }

    /* ── ANIMATIONS ── */
    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(18px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    @keyframes slideIn {
      from { width: 0; opacity: 0; }
      to   { width: 48px; opacity: 1; }
    }

    #view-register { max-height: calc(100vh - 200px); overflow-y: auto; padding-right: 4px; }
    #view-register::-webkit-scrollbar { width: 4px; }
    #view-register::-webkit-scrollbar-track { background: transparent; }
    #view-register::-webkit-scrollbar-thumb { background: var(--gray-2); border-radius: 2px; }

    /* ── RESPONSIVE ── */
    @media (max-width: 1024px) {
      .features-grid, .roles-grid { grid-template-columns: 1fr 1fr; }
      .steps-grid { grid-template-columns: 1fr 1fr; gap: 32px; }
      .steps-grid::before { display: none; }
      .lp-hero-inner { grid-template-columns: 1fr; gap: 48px; }
      .hero-right { display: none; }
      .lp-nav { padding: 16px 24px; }
      .lp-hero, .lp-features, .lp-how, .lp-roles, .lp-cta { padding-left: 24px; padding-right: 24px; }
      .lp-footer { padding: 20px 24px; flex-direction: column; gap: 12px; text-align: center; }
    }

    @media (max-width: 768px) {
      .features-grid, .roles-grid, .steps-grid { grid-template-columns: 1fr; }
      .lp-nav-links { display: none; }
      .card { padding: 32px 24px; }
    }
  </style>
</head>
<body>

<!-- NAV -->
<nav class="lp-nav">
  <a class="lp-nav-brand" href="#">
    <div class="lp-nav-icon">🎓</div>
    <div>
      <div class="lp-nav-name">EduTrack</div>
      <div class="lp-nav-tag">SANS</div>
    </div>
  </a>
  <ul class="lp-nav-links">
    <li><a href="#features">Features</a></li>
    <li><a href="#how-it-works">How It Works</a></li>
    <li><a href="#roles">Roles</a></li>
    <li><a href="#auth" class="lp-nav-cta">Sign In →</a></li>
  </ul>
</nav>

<!-- HERO -->
<section class="lp-hero">
  <div class="hero-blob-1"></div>
  <div class="hero-blob-2"></div>
  <div class="hero-blob-3"></div>
  <div class="lp-hero-inner">
    <div class="hero-left">
      <div class="hero-eyebrow"><span>🏫</span> Inabanga College of Arts &amp; Sciences</div>
      <h1 class="hero-h1">Every student<br>present &amp;<br><span class="gold-italic">accounted for.</span></h1>
      <p class="hero-desc">A unified platform for tracking daily attendance, sending real-time notifications to parents, and keeping educators informed — all in one place.</p>
      <div class="hero-actions">
        <a href="#auth" class="btn-hero-primary">Get Started →</a>
        <a href="#features" class="btn-hero-secondary">Learn More ↓</a>
      </div>
    </div>
    <div class="hero-right">
      <div class="hero-stats-card">
        <div class="hsc-label">📊 System Overview</div>
        <div class="hsc-stats">
          <div class="hsc-stat"><div class="hsc-stat-num">150+</div><div class="hsc-stat-label">Students</div></div>
          <div class="hsc-stat"><div class="hsc-stat-num">20</div><div class="hsc-stat-label">Classrooms</div></div>
          <div class="hsc-stat"><div class="hsc-stat-num">100%</div><div class="hsc-stat-label">Accuracy</div></div>
          <div class="hsc-stat"><div class="hsc-stat-num">Real-time</div><div class="hsc-stat-label">Notifications</div></div>
        </div>
        <div class="hsc-divider"></div>
        <div class="hsc-features">
          <div class="hsc-feature"><div class="hsc-feature-dot"></div>Automated parent SMS &amp; email alerts</div>
          <div class="hsc-feature"><div class="hsc-feature-dot"></div>QR code-based student check-in</div>
          <div class="hsc-feature"><div class="hsc-feature-dot"></div>Daily &amp; monthly attendance reports</div>
          <div class="hsc-feature"><div class="hsc-feature-dot"></div>Multi-role access control</div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- FEATURES -->
<section class="lp-features" id="features">
  <div class="section-inner">
    <div class="section-eyebrow">✨ Features</div>
    <h2 class="section-title">Everything you need<br>to track attendance</h2>
    <p class="section-sub">Built specifically for Inabanga College — a complete solution from check-in to parent notification.</p>
    <div class="features-grid">
      <div class="feature-card"><div class="feature-icon">📋</div><div class="feature-title">Daily Attendance</div><div class="feature-desc">Mark students as present, late, or absent with a single click. Attendance records are saved instantly and securely.</div></div>
      <div class="feature-card"><div class="feature-icon">🔔</div><div class="feature-title">Instant Notifications</div><div class="feature-desc">Parents receive automatic email alerts the moment their child is marked absent — no delays, no manual follow-up.</div></div>
      <div class="feature-card"><div class="feature-icon">📊</div><div class="feature-title">Reports Dashboard</div><div class="feature-desc">Visual attendance summaries with present, late, and absent counts. Filter by date and export records at any time.</div></div>
      <div class="feature-card"><div class="feature-icon">🔐</div><div class="feature-title">Role-based Access</div><div class="feature-desc">Admins, students, and parents each have their own dashboard with the right tools and information for their role.</div></div>
      <div class="feature-card"><div class="feature-icon">📱</div><div class="feature-title">QR Code Check-in</div><div class="feature-desc">Students receive a personal QR code upon registration for fast, contactless attendance verification in class.</div></div>
      <div class="feature-card"><div class="feature-icon">📅</div><div class="feature-title">Attendance History</div><div class="feature-desc">View complete historical records for any student. Identify patterns and flag students at risk of non-compliance.</div></div>
    </div>
  </div>
</section>

<!-- HOW IT WORKS -->
<section class="lp-how" id="how-it-works">
  <div class="section-inner">
    <div class="section-eyebrow">⚙️ How It Works</div>
    <h2 class="section-title">Simple. Fast. Reliable.</h2>
    <p class="section-sub" style="margin-bottom:60px;">From registration to real-time alerts in four easy steps.</p>
    <div class="steps-grid">
      <div class="step-item"><div class="step-num">1</div><div class="step-title">Register Account</div><div class="step-desc">Students, parents, and admins create their account and receive role-based access instantly.</div></div>
      <div class="step-item"><div class="step-num">2</div><div class="step-title">Mark Attendance</div><div class="step-desc">Admin marks each student present, late, or absent for the day from the Attendance page.</div></div>
      <div class="step-item"><div class="step-num">3</div><div class="step-title">Notify Parents</div><div class="step-desc">The system automatically emails parents when their child is marked absent or late.</div></div>
      <div class="step-item"><div class="step-num">4</div><div class="step-title">Review Reports</div><div class="step-desc">Admins and parents can view daily and historical attendance records from their dashboard.</div></div>
    </div>
  </div>
</section>

<!-- ROLES -->
<section class="lp-roles" id="roles">
  <div class="section-inner">
    <div class="section-eyebrow">👥 Who It's For</div>
    <h2 class="section-title">Built for everyone<br>in the school</h2>
    <div class="roles-grid">
      <div class="role-card admin-card">
        <div class="role-badge">🛡️ Administrator</div>
        <div class="role-title">Full Control</div>
        <div class="role-desc">Complete oversight of students, attendance records, and system notifications.</div>
        <ul class="role-perks"><li>Manage all student records</li><li>Mark and edit attendance daily</li><li>Send notifications to parents</li><li>View reports &amp; analytics</li></ul>
      </div>
      <div class="role-card student-card">
        <div class="role-badge">🎓 Student</div>
        <div class="role-title">Stay Informed</div>
        <div class="role-desc">Students can track their own attendance and view their personal records anytime.</div>
        <ul class="role-perks"><li>View personal attendance history</li><li>Receive QR code on registration</li><li>Check present/absent/late status</li><li>Update profile information</li></ul>
      </div>
      <div class="role-card parent-card">
        <div class="role-badge">👨‍👩‍👧 Parent</div>
        <div class="role-title">Always Aware</div>
        <div class="role-desc">Parents are automatically notified and can monitor their child's attendance anytime.</div>
        <ul class="role-perks"><li>Instant absence email alerts</li><li>View child's attendance records</li><li>Monitor attendance trends</li><li>Secure parent portal access</li></ul>
      </div>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="lp-cta">
  <div class="cta-box">
    <div class="section-eyebrow" style="margin:0 auto 16px;">🚀 Get Started Today</div>
    <h2 class="section-title">Ready to track attendance<br>the smart way?</h2>
    <p class="section-sub">Join Inabanga College's unified attendance system. Sign in or create your account below.</p>
    <div class="cta-actions">
      <a href="#auth" class="btn-hero-primary">Sign In Now →</a>
      <a href="#auth" class="btn-hero-secondary" onclick="setTimeout(()=>switchTab('register'),100)">Create Account</a>
    </div>
  </div>
</section>

<!-- FOOTER -->
<footer class="lp-footer">
  <a class="footer-brand" href="#">
    <div class="footer-brand-icon">🎓</div>
    <div class="footer-brand-name">EduTrack SANS</div>
  </a>
  <span class="footer-copy">© 2026 Inabanga College of Arts &amp; Sciences. All rights reserved.</span>
  <ul class="footer-links">
    <li><a href="#features">Features</a></li>
    <li><a href="#how-it-works">How It Works</a></li>
    <li><a href="#auth">Sign In</a></li>
  </ul>
</footer>

<!-- AUTH SECTION — centered card, no left panel -->
<div id="auth" class="auth-section">
  <div class="page">
    <div class="card">

      <!-- TABS -->
      <div class="tab-row">
        <button class="tab-btn active" id="tab-login" onclick="switchTab('login')">Sign In</button>
        <button class="tab-btn" id="tab-register" onclick="switchTab('register')">Register</button>
      </div>

      <!-- LOGIN FORM -->
      <div class="form-view active" id="view-login">
        <?php if (isset($_GET['error']) && !isset($_GET['from_register'])): ?>
          <div class="alert alert-error">⚠️ <?= htmlspecialchars($_GET['error']) ?></div>
        <?php endif; ?>
        <div class="form-title">Welcome back</div>
        <div class="form-sub">Sign in to your EduTrack account</div>
        <form action="login.php" method="POST">
          <div class="field-group">
            <label class="field-label">Email Address</label>
            <div class="field-wrap">
              <span class="field-icon">✉️</span>
              <input type="email" name="email" placeholder="your@school.edu" required>
            </div>
          </div>
          <div class="field-group">
            <label class="field-label">Password</label>
            <div class="field-wrap">
              <span class="field-icon">🔒</span>
              <input type="password" name="password" placeholder="Enter your password" required>
            </div>
          </div>
          <div class="field-meta">
            <a href="#" class="forgot-link">Forgot password?</a>
          </div>
          <button class="btn-submit" type="submit" name="login">Sign In →</button>
        </form>
      </div>

      <!-- REGISTER FORM -->
      <div class="form-view" id="view-register">
        <?php if (isset($_GET['error'])): ?>
          <div class="alert alert-error">⚠️ <?= htmlspecialchars($_GET['error']) ?></div>
        <?php endif; ?>
        <div class="form-title">Create account</div>
        <div class="form-sub">Join EduTrack and get started today</div>
        <form action="register.php" method="POST">
          <div class="field-group">
            <label class="field-label">Full Name</label>
            <div class="field-wrap">
              <span class="field-icon">👤</span>
              <input type="text" name="fullname" placeholder="e.g. Maria Santos" required>
            </div>
          </div>
          <div class="field-group">
            <label class="field-label">Email Address</label>
            <div class="field-wrap">
              <span class="field-icon">✉️</span>
              <input type="email" name="email" placeholder="your@school.edu" required>
            </div>
          </div>
          <div class="field-group">
            <label class="field-label">Role</label>
            <div class="field-wrap">
              <span class="field-icon">🏫</span>
              <select name="role" id="role-select" required onchange="toggleParentEmail(this.value)">
                <option value="">Select your role</option>
                <option value="admin">Administrator</option>
                <option value="student">Student</option>
                <option value="parent">Parent</option>
              </select>
            </div>
          </div>
          <div class="field-group" id="parent-email-field" style="display:none;">
            <label class="field-label">Parent Gmail <span style="color:#DC2626;">*</span></label>
            <div class="field-wrap">
              <span class="field-icon">📧</span>
              <input type="email" name="parent_email" id="parent_email" placeholder="parent@gmail.com">
            </div>
            <div class="field-hint">Your parent will be notified if you miss attendance.</div>
          </div>
          <div class="field-group">
            <label class="field-label">Password</label>
            <div class="field-wrap">
              <span class="field-icon">🔒</span>
              <input type="password" name="password" placeholder="Create a password" required>
            </div>
          </div>
          <div class="field-group">
            <label class="field-label">Confirm Password</label>
            <div class="field-wrap">
              <span class="field-icon">🔒</span>
              <input type="password" name="confirm_password" placeholder="Re-enter your password" required>
            </div>
          </div>
          <button type="submit" name="register" class="btn-submit">Create Account →</button>
        </form>
      </div>

      <!-- SUCCESS SPLASH -->
      <div class="success-splash" id="reg-success">
        <div class="splash-icon-wrap">✅</div>
        <div class="splash-title">Account Created!</div>
        <div class="splash-sub">
          Your account has been registered.<br>
          Check your email for your QR code,<br>then sign in with your credentials.
        </div>
        <button class="splash-back" onclick="goToLogin()">← Back to Sign In</button>
      </div>

    </div>
  </div>
</div>

<script>
  function toggleParentEmail(role) {
    const field = document.getElementById('parent-email-field');
    const input = document.getElementById('parent_email');
    if (role === 'student') {
      field.style.display = 'block';
      input.required = true;
    } else {
      field.style.display = 'none';
      input.required = false;
      input.value = '';
    }
  }

  function switchTab(tab) {
    ['view-login','view-register'].forEach(id =>
      document.getElementById(id).classList.remove('active'));
    ['tab-login','tab-register'].forEach(id =>
      document.getElementById(id).classList.remove('active'));
    document.getElementById('view-' + tab).classList.add('active');
    document.getElementById('tab-' + tab).classList.add('active');
  }

  function goToLogin() {
    document.getElementById('reg-success').style.display = 'none';
    switchTab('login');
  }

  <?php if (isset($_GET['error'])): ?>
    switchTab('register');
  <?php endif; ?>

  <?php if (isset($_GET['success']) && $_GET['success'] === 'registered'): ?>
    document.getElementById('view-login').classList.remove('active');
    document.getElementById('view-register').classList.remove('active');
    document.getElementById('reg-success').style.display = 'flex';
  <?php endif; ?>

  document.querySelectorAll('a[href^="#"]').forEach(a => {
    a.addEventListener('click', e => {
      const target = document.querySelector(a.getAttribute('href'));
      if (target) {
        e.preventDefault();
        const offset = 70;
        const top = target.getBoundingClientRect().top + window.scrollY - offset;
        window.scrollTo({ top, behavior: 'smooth' });
      }
    });
  });
</script>

</body>
</html>