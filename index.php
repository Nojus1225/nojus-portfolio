<?php
session_start();

if (!file_exists('data')) mkdir('data', 0777, true);
if (!file_exists('uploads')) mkdir('uploads', 0777, true);
if (!file_exists('uploads/gallery')) mkdir('uploads/gallery', 0777, true);

if (!file_exists('data/settings.json')) {
    file_put_contents('data/settings.json', json_encode([
        'site_title' => 'Nojus Portfolio',
        'hero_name' => 'Nojus',
        'hero_role' => 'Product Designer & Developer',
        'hero_bio' => 'Creating digital products that are simple, human, and meaningful.',
        'avatar' => '',
        'stats_years' => '3+',
        'stats_projects' => '15',
        'stats_clients' => '10',
        'discord_id' => '',
        'github_url' => '',
        'linkedin_url' => '',
        'seo_title' => 'Nojus | Portfolio',
        'seo_description' => 'Portfolio of Nojus - Product Designer & Developer',
        'seo_keywords' => 'portfolio, designer, developer, web, nojus',
        'languages' => [
            ['code' => 'en', 'name' => 'English', 'flag' => '🇬🇧'],
            ['code' => 'lt', 'name' => 'Lietuvių', 'flag' => '🇱🇹']
        ],
        'features' => [
            ['icon' => '🎨', 'title' => 'UI/UX Design', 'desc' => 'Beautiful, intuitive interfaces.'],
            ['icon' => '⚡', 'title' => 'Fast Development', 'desc' => 'Clean code with performance.'],
            ['icon' => '📱', 'title' => 'Responsive Design', 'desc' => 'Perfect on all devices.']
        ],
        'skills' => [
            ['name' => 'PHP', 'level' => 85, 'years' => 5],
            ['name' => 'JavaScript', 'level' => 75, 'years' => 4],
            ['name' => 'HTML/CSS', 'level' => 90, 'years' => 6],
            ['name' => 'React', 'level' => 70, 'years' => 3]
        ]
    ], JSON_PRETTY_PRINT));
}

if (!file_exists('data/projects.json')) {
    file_put_contents('data/projects.json', json_encode([
        ['id' => 1, 'title' => 'Finance App', 'short_desc' => 'Mobile banking reimagined.', 'icon' => '', 'date' => '2024-03-15', 'language' => 'en', 'gallery' => [], 'content_md' => "# Finance App\n\nDemo content"],
        ['id' => 2, 'title' => 'E-commerce Brand', 'short_desc' => 'Complete UI/UX for sustainable fashion.', 'icon' => '', 'date' => '2024-01-10', 'language' => 'lt', 'gallery' => [], 'content_md' => "# E-commerce Brand\n\nDemo content"]
    ], JSON_PRETTY_PRINT));
}

$projects = json_decode(file_get_contents('data/projects.json'), true);
$settings = json_decode(file_get_contents('data/settings.json'), true);
if (!is_array($projects)) $projects = [];
if (!is_array($settings)) $settings = [];

$hasProjects = count($projects) > 0;
$discordLink = !empty($settings['discord_id']) ? 'https://discord.com/users/' . $settings['discord_id'] : '#';

function getLanguageByCode($code, $languages) {
    foreach ($languages as $lang) {
        if ($lang['code'] == $code) return $lang;
    }
    return null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($settings['seo_title'] ?? $settings['site_title'] ?? 'Portfolio') ?></title>
    <meta name="description" content="<?= htmlspecialchars($settings['seo_description'] ?? '') ?>">
    <meta name="keywords" content="<?= htmlspecialchars($settings['seo_keywords'] ?? '') ?>">
    <link rel="stylesheet" href="assets/global.css">
    <link href="https://unpkg.com/aos@next/dist/aos.css" rel="stylesheet">
    <style>
        .projects-section { max-width: 1200px; margin: 0 auto; padding: 60px 24px; }
        .projects-title, .section-title { text-align: center; font-size: clamp(26px, 4vw, 38px); font-weight: 800; margin-bottom: 12px; }
        .projects-sub, .section-sub { text-align: center; color: var(--muted); margin-bottom: 48px; }
        .projects-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 24px; }
        .project-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: 20px; overflow: hidden; transition: all 0.3s ease; }
        .project-card:hover { transform: translateY(-6px); border-color: rgba(88,101,242,0.4); box-shadow: 0 20px 40px rgba(0,0,0,0.3); }
        .project-icon { width: 100%; height: 180px; object-fit: cover; background: var(--bg-card2); display: flex; align-items: center; justify-content: center; font-size: 64px; }
        .project-icon img { width: 100%; height: 100%; object-fit: cover; }
        .project-content { padding: 24px; }
        .project-content h3 { font-size: 22px; margin-bottom: 8px; }
        .project-content p { color: var(--muted); font-size: 14px; margin-bottom: 12px; }
        .project-date { font-size: 12px; color: var(--primary); margin-bottom: 8px; display: inline-block; }
        .project-language { display: inline-block; margin-left: 12px; font-size: 12px; background: var(--bg-card2); padding: 2px 8px; border-radius: 20px; }
        .time-section { max-width: 1200px; margin: 10px auto 40px; display: flex; justify-content: center; gap: 24px; flex-wrap: wrap; padding: 0 24px; }
        .time-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: 16px; padding: 16px 28px; text-align: center; min-width: 200px; }
        .time-label { font-size: 13px; color: var(--muted); text-transform: uppercase; letter-spacing: 1px; }
        .time-value { font-size: 28px; font-weight: 700; font-family: monospace; color: var(--primary); }
        .no-projects { text-align: center; padding: 60px; background: var(--bg-card); border-radius: 20px; margin: 40px auto; max-width: 500px; }

        .skills-section { max-width: 1100px; margin: 0 auto; padding: 80px 24px; width: 100%; }
        .skills-grid-identical { display: grid; grid-template-columns: repeat(auto-fill, minmax(290px, 1fr)); gap: 20px; }
        .skill-card-identical { background: var(--bg-card); border: 1px solid var(--border); border-radius: 16px; padding: 28px 26px; transition: all 0.25s; }
        .skill-card-identical:hover { border-color: rgba(88,101,242,0.4); transform: translateY(-4px); }
        .skill-name { font-size: 20px; font-weight: 700; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: baseline; }
        .skill-years { font-size: 14px; color: var(--primary); }
        .skill-bar { background: var(--bg); border-radius: 10px; height: 8px; margin: 16px 0; overflow: hidden; }
        .skill-progress { background: var(--primary); height: 100%; border-radius: 10px; }
        .skill-level { font-size: 14px; color: var(--muted); text-align: right; }
        @media (max-width: 768px) { .skills-grid-identical { grid-template-columns: 1fr; } }

        .project-search { width: 100%; max-width: 400px; margin: 0 auto 40px; display: block; padding: 12px 20px; background: var(--bg-card); border: 1px solid var(--border); border-radius: 40px; color: var(--text); font-size: 14px; }
        .project-search:focus { outline: none; border-color: var(--primary); }
        .no-results { text-align: center; padding: 40px; color: var(--muted); display: none; }
        .toast { position: fixed; bottom: 20px; left: 20px; background: var(--bg-card); border-left: 4px solid var(--green); color: var(--text); padding: 12px 20px; border-radius: 12px; box-shadow: 0 8px 20px rgba(0,0,0,0.3); z-index: 9999; display: flex; align-items: center; gap: 12px; font-size: 14px; animation: slideIn 0.3s; }
        .toast.error { border-left-color: #dc2626; }
        .toast.hide { animation: fadeOut 0.3s forwards; }
        @keyframes slideIn { from { transform: translateX(-100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        @keyframes fadeOut { to { opacity: 0; visibility: hidden; } }
        
        .social-links { display: flex; justify-content: center; gap: 20px; margin-top: 10px; }
        .social-icon { font-size: 20px; color: var(--muted); transition: color 0.2s; }
        .social-icon:hover { color: var(--primary); }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="navbar-logo"><span>🎯</span><span><?= htmlspecialchars($settings['hero_name'] ?? 'Portfolio') ?></span></div>
    <div class="navbar-links"><a href="#">Home</a><a href="#projects">Work</a><a href="#skills">Skills</a><a href="#contact">Contact</a></div>
    <div class="navbar-right">
        <?php if (isset($_SESSION['admin_logged_in'])): ?><a href="admin.php" style="background: var(--primary); padding: 6px 14px; border-radius: 8px;">Admin</a><?php endif; ?>
    </div>
</nav>

<section class="hero">
    <div class="hero-inner">
        <?php if (!empty($settings['avatar']) && file_exists($settings['avatar'])): ?>
            <img src="<?= htmlspecialchars($settings['avatar']) ?>" alt="Avatar" class="hero-avatar">
        <?php else: ?>
            <div class="hero-avatar" style="background: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 48px;">👨‍💻</div>
        <?php endif; ?>
        <div class="hero-badge"><span class="dot"></span><span>Available for work</span></div>
        <h1 class="hero-title"><?= htmlspecialchars($settings['hero_name'] ?? 'Nojus') ?><br><?= htmlspecialchars($settings['hero_role'] ?? 'Developer') ?></h1>
        <p class="hero-sub"><?= htmlspecialchars($settings['hero_bio'] ?? 'Building digital experiences.') ?></p>
        <div class="hero-actions">
            <a href="#projects" class="btn-primary">🎯 View work</a>
            <a href="<?= $discordLink ?>" class="btn-secondary" target="_blank" rel="noopener noreferrer">📧 Contact me on Discord</a>
        </div>
    </div>
</section>

<div class="stats">
    <div class="stat-item"><div class="stat-num"><?= htmlspecialchars($settings['stats_years'] ?? '3+') ?></div><div class="stat-label">Years of experience</div></div>
    <div class="stat-item"><div class="stat-num"><?= htmlspecialchars($settings['stats_projects'] ?? '15') ?></div><div class="stat-label">Projects completed</div></div>
    <div class="stat-item"><div class="stat-num"><?= htmlspecialchars($settings['stats_clients'] ?? '10') ?></div><div class="stat-label">Happy clients</div></div>
</div>

<div class="time-section">
    <div class="time-card"><div class="time-label">🇱🇹 Lithuania time</div><div class="time-value" id="lt-time">--:--:--</div></div>
    <div class="time-card"><div class="time-label">🌍 Your browser time</div><div class="time-value" id="browser-time">--:--:--</div></div>
</div>

<section class="features" id="features" data-aos="fade-up">
    <h2 class="section-title">What I do</h2>
    <p class="section-sub">Services I provide to help your business grow</p>
    <div class="features-grid">
        <?php foreach ($settings['features'] as $feature): ?>
        <div class="feature-card" data-aos="fade-up" data-aos-delay="100">
            <div class="feature-icon"><?= htmlspecialchars($feature['icon']) ?></div>
            <h3><?= htmlspecialchars($feature['title']) ?></h3>
            <p><?= htmlspecialchars($feature['desc']) ?></p>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="skills-section" id="skills" data-aos="fade-up">
    <h2 class="section-title">Technical Skills</h2>
    <p class="section-sub">Technologies I work with and my experience level</p>
    <div class="skills-grid-identical">
        <?php foreach ($settings['skills'] as $skill): ?>
        <div class="skill-card-identical" data-aos="fade-up" data-aos-delay="100">
            <div class="skill-name">
                <?= htmlspecialchars($skill['name']) ?>
                <span class="skill-years"><?= $skill['years'] ?> <?= $skill['years'] == 1 ? 'year' : 'years' ?></span>
            </div>
            <div class="skill-bar">
                <div class="skill-progress" style="width: <?= $skill['level'] ?>%;"></div>
            </div>
            <div class="skill-level"><?= $skill['level'] ?>% proficiency</div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="projects-section" id="projects" data-aos="fade-up">
    <h2 class="projects-title">Featured Work</h2>
    <p class="projects-sub">Some of my recent projects</p>
    <input type="text" id="project-search" class="project-search" placeholder="🔍 Search projects...">
    <?php if (!$hasProjects): ?>
        <div class="no-projects"><p>🚀 No projects available yet.</p><?php if (isset($_SESSION['admin_logged_in'])): ?><p><a href="admin.php">Go to admin panel</a> and add your first project.</p><?php else: ?><p><a href="login.php">Admin login</a> to add projects.</p><?php endif; ?></div>
    <?php else: ?>
        <div class="projects-grid" id="projects-grid">
            <?php foreach ($projects as $p): 
                $lang = getLanguageByCode($p['language'] ?? 'en', $settings['languages'] ?? []);
            ?>
            <div class="project-card" data-title="<?= strtolower(htmlspecialchars($p['title'])) ?>" data-desc="<?= strtolower(htmlspecialchars($p['short_desc'])) ?>">
                <div class="project-icon"><?php if (!empty($p['icon']) && file_exists($p['icon'])): ?><img src="<?= htmlspecialchars($p['icon']) ?>" alt="<?= htmlspecialchars($p['title']) ?>"><?php else: ?>📁<?php endif; ?></div>
                <div class="project-content">
                    <h3><?= htmlspecialchars($p['title']) ?></h3>
                    <p><?= htmlspecialchars($p['short_desc']) ?></p>
                    <div>
                        <span class="project-date">📅 <?= date('F Y', strtotime($p['date'])) ?></span>
                        <?php if ($lang): ?>
                            <span class="project-language"><?= htmlspecialchars($lang['flag']) ?> <?= htmlspecialchars($lang['name']) ?></span>
                        <?php endif; ?>
                    </div>
                    <br>
                    <a href="project.php?id=<?= $p['id'] ?>" class="btn-primary" style="padding: 10px 20px; font-size: 14px;">View project →</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div id="no-results" class="no-results">No projects found.</div>
    <?php endif; ?>
</section>

<section class="cta-banner" id="contact" data-aos="fade-up">
    <h2>Let's work together</h2>
    <p>Have a project in mind? I'd love to hear about it.</p>
    <a href="<?= $discordLink ?>" class="btn-primary" target="_blank" rel="noopener noreferrer">💬 Message me on Discord</a>
</section>

<footer class="footer">
    <div class="footer-logo"><span>🎯</span><span><?= htmlspecialchars($settings['hero_name'] ?? 'Portfolio') ?></span></div>
    <div class="footer-links">
        <a href="#">Home</a>
        <a href="#projects">Work</a>
        <a href="#skills">Skills</a>
        <a href="#contact">Contact</a>
        <?php if (!empty($settings['github_url'])): ?>
            <a href="<?= htmlspecialchars($settings['github_url']) ?>" target="_blank">GitHub</a>
        <?php endif; ?>
        <?php if (!empty($settings['linkedin_url'])): ?>
            <a href="<?= htmlspecialchars($settings['linkedin_url']) ?>" target="_blank">LinkedIn</a>
        <?php endif; ?>
    </div>
    <div class="social-links">
        <?php if (!empty($settings['github_url'])): ?>
            <a href="<?= htmlspecialchars($settings['github_url']) ?>" target="_blank" class="social-icon">🐙 GitHub</a>
        <?php endif; ?>
        <?php if (!empty($settings['linkedin_url'])): ?>
            <a href="<?= htmlspecialchars($settings['linkedin_url']) ?>" target="_blank" class="social-icon">🔗 LinkedIn</a>
        <?php endif; ?>
        <?php if (!empty($settings['discord_id'])): ?>
            <a href="https://discord.com/users/<?= htmlspecialchars($settings['discord_id']) ?>" target="_blank" class="social-icon">💬 Discord</a>
        <?php endif; ?>
    </div>
    <div class="footer-copy">© <?= date('Y') ?> <?= htmlspecialchars($settings['hero_name'] ?? 'Portfolio') ?>. All rights reserved.</div>
</footer>

<script src="https://unpkg.com/aos@next/dist/aos.js"></script>
<script>
    AOS.init({ duration: 800, once: true });
    
    function updateTimes() {
        const lt = document.getElementById('lt-time');
        if (lt) lt.textContent = new Intl.DateTimeFormat('en-GB', { timeZone: 'Europe/Vilnius', hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false }).format(new Date());
        const br = document.getElementById('browser-time');
        if (br) br.textContent = new Date().toLocaleTimeString('en-GB', { hour12: false });
    }
    updateTimes(); setInterval(updateTimes, 1000);
    
    const searchInput = document.getElementById('project-search');
    const noResultsDiv = document.getElementById('no-results');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const term = this.value.toLowerCase();
            const cards = document.querySelectorAll('.project-card');
            let visible = 0;
            cards.forEach(card => {
                const title = card.getAttribute('data-title') || '';
                const desc = card.getAttribute('data-desc') || '';
                if (title.includes(term) || desc.includes(term)) { card.style.display = 'block'; visible++; }
                else { card.style.display = 'none'; }
            });
            if (noResultsDiv) noResultsDiv.style.display = visible === 0 ? 'block' : 'none';
        });
    }
    
    function showToast(msg, type='success') {
        const toast = document.createElement('div'); toast.className = `toast ${type}`;
        toast.innerHTML = `<span>${type==='success'?'✅':'❌'}</span> ${msg}`;
        document.body.appendChild(toast);
        setTimeout(() => { toast.classList.add('hide'); setTimeout(() => toast.remove(), 300); }, 3000);
    }
    
    <?php if (isset($_SESSION['admin_success'])): ?>
    showToast('<?= addslashes($_SESSION['admin_success']) ?>', 'success');
    <?php unset($_SESSION['admin_success']); endif; ?>
    <?php if (isset($_SESSION['admin_error'])): ?>
    showToast('<?= addslashes($_SESSION['admin_error']) ?>', 'error');
    <?php unset($_SESSION['admin_error']); endif; ?>
</script>
</body>
</html>