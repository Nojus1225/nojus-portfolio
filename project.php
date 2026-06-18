<?php
if (!file_exists('data/projects.json')) {
    header('Location: index.php');
    exit;
}
if (!file_exists('data/settings.json')) {
    header('Location: index.php');
    exit;
}

$projects = json_decode(file_get_contents('data/projects.json'), true);
$settings = json_decode(file_get_contents('data/settings.json'), true);
if (!is_array($projects)) $projects = [];
if (!is_array($settings)) $settings = [];

function getLanguageByCode($code, $languages) {
    foreach ($languages as $lang) {
        if ($lang['code'] == $code) return $lang;
    }
    return null;
}

$project = null;
if (isset($_GET['id'])) {
    foreach ($projects as $p) {
        if ($p['id'] == $_GET['id']) {
            $project = $p;
            break;
        }
    }
}

if (!$project) {
    header('HTTP/1.0 404 Not Found');
    echo '<!DOCTYPE html>
    <html lang="en">
    <head><meta charset="UTF-8"><title>404 - Project Not Found</title><link rel="stylesheet" href="assets/global.css"></head>
    <body style="background:var(--bg); display:flex; align-items:center; justify-content:center; height:100vh;">
        <div style="text-align:center;">
            <h1>404</h1>
            <p>Project not found.</p>
            <a href="index.php" class="btn-primary">← Back to portfolio</a>
        </div>
    </body>
    </html>';
    exit;
}

require_once 'includes/Parsedown.php';
$Parsedown = new Parsedown();

$lang = getLanguageByCode($project['language'] ?? 'en', $settings['languages'] ?? []);
$gallery = $project['gallery'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($project['title']) ?> | <?= htmlspecialchars($settings['site_title'] ?? 'Portfolio') ?></title>
    <link rel="stylesheet" href="assets/global.css">
    <style>
        .project-detail {
            max-width: 1100px;
            margin: 100px auto 60px;
            padding: 0 24px;
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--muted);
            margin-bottom: 32px;
            transition: color 0.2s;
        }
        .back-link:hover { color: var(--text); }
        .project-header { margin-bottom: 40px; }
        .project-header h1 { font-size: 48px; margin-bottom: 16px; }
        .project-icon-large { max-width: 120px; margin-bottom: 24px; }
        .project-icon-large img { width: 100%; border-radius: 20px; }
        .project-meta { display: flex; gap: 16px; align-items: center; flex-wrap: wrap; margin-bottom: 20px; }
        .project-date { color: var(--primary); font-size: 14px; }
        .project-language { background: var(--bg-card2); padding: 4px 12px; border-radius: 30px; font-size: 14px; }
        
        /* Galerijos stilius */
        .gallery-section {
            margin: 40px 0;
        }
        .gallery-title {
            font-size: 24px;
            margin-bottom: 20px;
            color: var(--text);
        }
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }
        .gallery-item {
            background: var(--bg-card);
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid var(--border);
            transition: transform 0.2s;
            cursor: pointer;
        }
        .gallery-item:hover {
            transform: scale(1.02);
            border-color: var(--primary);
        }
        .gallery-item img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            display: block;
        }
        
        /* Lightbox (paprastas) */
        .lightbox {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.9);
            z-index: 10000;
            justify-content: center;
            align-items: center;
            cursor: pointer;
        }
        .lightbox.active {
            display: flex;
        }
        .lightbox img {
            max-width: 90%;
            max-height: 90%;
            border-radius: 12px;
        }
        
        .markdown-content {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 40px;
            margin-top: 40px;
        }
        .markdown-content h1 { font-size: 32px; margin: 24px 0 16px; }
        .markdown-content h2 { font-size: 24px; margin: 20px 0 12px; color: var(--primary); }
        .markdown-content p { margin: 16px 0; line-height: 1.7; }
        .markdown-content ul, .markdown-content ol { margin: 16px 0; padding-left: 24px; }
        .markdown-content li { margin: 8px 0; }
        .markdown-content blockquote {
            border-left: 4px solid var(--primary);
            padding-left: 20px;
            margin: 20px 0;
            color: var(--muted);
            font-style: italic;
        }
        .markdown-content a { color: var(--primary); text-decoration: underline; }
        .markdown-content img { max-width: 100%; border-radius: 12px; margin: 20px 0; }
        
        @media (max-width: 768px) {
            .project-detail { margin: 80px auto 40px; }
            .project-header h1 { font-size: 32px; }
            .markdown-content { padding: 24px; }
            .gallery-grid { grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); }
        }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="navbar-logo"><span>🎯</span><span><?= htmlspecialchars($settings['hero_name'] ?? 'Portfolio') ?></span></div>
    <div class="navbar-links"><a href="index.php">Home</a><a href="index.php#projects">Work</a><a href="index.php#skills">Skills</a><a href="index.php#contact">Contact</a></div>
</nav>

<div class="project-detail">
    <a href="index.php" class="back-link">← Back to portfolio</a>
    
    <div class="project-header">
        <?php if (!empty($project['icon']) && file_exists($project['icon'])): ?>
            <div class="project-icon-large">
                <img src="<?= htmlspecialchars($project['icon']) ?>" alt="<?= htmlspecialchars($project['title']) ?>">
            </div>
        <?php endif; ?>
        <h1><?= htmlspecialchars($project['title']) ?></h1>
        <div class="project-meta">
            <div class="project-date">📅 <?= date('F Y', strtotime($project['date'])) ?></div>
            <?php if ($lang): ?>
                <div class="project-language"><?= htmlspecialchars($lang['flag']) ?> <?= htmlspecialchars($lang['name']) ?></div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if (!empty($gallery)): ?>
    <div class="gallery-section">
        <h2 class="gallery-title">📸 Project Gallery</h2>
        <div class="gallery-grid">
            <?php foreach ($gallery as $img): ?>
                <div class="gallery-item" onclick="openLightbox('<?= htmlspecialchars($img) ?>')">
                    <img src="<?= htmlspecialchars($img) ?>" alt="Gallery image">
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="markdown-content">
        <?= $Parsedown->text($project['content_md'] ?? '') ?>
    </div>
</div>

<!-- Lightbox -->
<div id="lightbox" class="lightbox" onclick="closeLightbox()">
    <img id="lightbox-img" src="">
</div>

<footer class="footer">
    <div class="footer-copy">
        © <?= date('Y') ?> <?= htmlspecialchars($settings['hero_name'] ?? 'Portfolio') ?>. All rights reserved.
    </div>
</footer>

<script>
    function openLightbox(src) {
        const lightbox = document.getElementById('lightbox');
        const img = document.getElementById('lightbox-img');
        img.src = src;
        lightbox.classList.add('active');
    }
    function closeLightbox() {
        const lightbox = document.getElementById('lightbox');
        lightbox.classList.remove('active');
    }
</script>
</body>
</html>