<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

function saveJson($file, $data) {
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT), LOCK_EX);
}

if (!file_exists('data')) mkdir('data', 0777, true);
if (!file_exists('uploads')) mkdir('uploads', 0777, true);
if (!file_exists('uploads/gallery')) mkdir('uploads/gallery', 0777, true);

if (!file_exists('data/settings.json')) {
    saveJson('data/settings.json', [
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
    ]);
}
if (!file_exists('data/projects.json')) {
    saveJson('data/projects.json', [
        ['id' => 1, 'title' => 'Finance App', 'short_desc' => 'Mobile banking reimagined.', 'icon' => '', 'date' => '2024-03-15', 'language' => 'en', 'gallery' => [], 'content_md' => "# Finance App\n\nDemo content"],
        ['id' => 2, 'title' => 'E-commerce Brand', 'short_desc' => 'Complete UI/UX for sustainable fashion.', 'icon' => '', 'date' => '2024-01-10', 'language' => 'lt', 'gallery' => [], 'content_md' => "# E-commerce Brand\n\nDemo content"]
    ]);
}

$projects = json_decode(file_get_contents('data/projects.json'), true);
$settings = json_decode(file_get_contents('data/settings.json'), true);
if (!is_array($projects)) $projects = [];
if (!is_array($settings)) $settings = [];

// Delete project
if (isset($_GET['delete_project'])) {
    $newProjects = [];
    foreach ($projects as $p) {
        if ($p['id'] != $_GET['delete_project']) $newProjects[] = $p;
    }
    saveJson('data/projects.json', $newProjects);
    $_SESSION['admin_success'] = "Project deleted successfully.";
    header('Location: admin.php?tab=projects');
    exit;
}

// Delete skill
if (isset($_GET['delete_skill'])) {
    $index = (int)$_GET['delete_skill'];
    if (isset($settings['skills'][$index])) {
        $newSkills = [];
        foreach ($settings['skills'] as $i => $s) {
            if ($i != $index) $newSkills[] = $s;
        }
        $settings['skills'] = $newSkills;
        saveJson('data/settings.json', $settings);
        $_SESSION['admin_success'] = "Skill deleted successfully.";
    }
    header('Location: admin.php?tab=skills');
    exit;
}

// Delete language
if (isset($_GET['delete_language'])) {
    $index = (int)$_GET['delete_language'];
    if (isset($settings['languages'][$index])) {
        $newLanguages = [];
        foreach ($settings['languages'] as $i => $lang) {
            if ($i != $index) $newLanguages[] = $lang;
        }
        $settings['languages'] = $newLanguages;
        saveJson('data/settings.json', $settings);
        $_SESSION['admin_success'] = "Language deleted successfully.";
    }
    header('Location: admin.php?tab=languages');
    exit;
}

// Delete gallery image
if (isset($_GET['delete_gallery']) && isset($_GET['project_id'])) {
    $projectId = (int)$_GET['project_id'];
    $imgIndex = (int)$_GET['delete_gallery'];
    foreach ($projects as $i => $p) {
        if ($p['id'] == $projectId) {
            if (isset($p['gallery'][$imgIndex])) {
                $imgPath = $p['gallery'][$imgIndex];
                if (file_exists($imgPath)) unlink($imgPath);
                array_splice($projects[$i]['gallery'], $imgIndex, 1);
                saveJson('data/projects.json', $projects);
                $_SESSION['admin_success'] = "Image deleted successfully.";
            }
            break;
        }
    }
    header("Location: admin.php?tab=projects&edit_project={$projectId}");
    exit;
}

// Save project (with gallery)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_project'])) {
    $id = ($_POST['id'] === 'new') ? time() : (int)$_POST['id'];
    
    // Find existing project to preserve gallery
    $existingGallery = [];
    foreach ($projects as $p) {
        if ($p['id'] == $id) {
            $existingGallery = $p['gallery'] ?? [];
            break;
        }
    }
    
    $projectData = [
        'id' => $id,
        'title' => $_POST['title'],
        'short_desc' => $_POST['short_desc'],
        'icon' => '',
        'date' => $_POST['date'],
        'language' => $_POST['language'],
        'gallery' => $existingGallery,
        'content_md' => $_POST['content_md']
    ];
    
    if (!empty($_FILES['icon']['name'])) {
        $ext = strtolower(pathinfo($_FILES['icon']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($ext, $allowed)) {
            $filename = 'uploads/icon_' . $id . '.' . $ext;
            move_uploaded_file($_FILES['icon']['tmp_name'], $filename);
            $projectData['icon'] = $filename;
        }
    } elseif ($_POST['id'] !== 'new' && isset($_POST['old_icon']) && !empty($_POST['old_icon'])) {
        $projectData['icon'] = $_POST['old_icon'];
    }
    
    // Handle gallery uploads
    if (!empty($_FILES['gallery_images']['name'][0])) {
        $galleryDir = 'uploads/gallery/';
        foreach ($_FILES['gallery_images']['tmp_name'] as $idx => $tmpName) {
            if (!empty($tmpName)) {
                $ext = strtolower(pathinfo($_FILES['gallery_images']['name'][$idx], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (in_array($ext, $allowed)) {
                    $newFilename = $galleryDir . $id . '_' . time() . '_' . $idx . '.' . $ext;
                    if (move_uploaded_file($tmpName, $newFilename)) {
                        $projectData['gallery'][] = $newFilename;
                    }
                }
            }
        }
    }
    
    $updated = false;
    foreach ($projects as $i => $p) {
        if ($p['id'] == $id) {
            $projects[$i] = $projectData;
            $updated = true;
            break;
        }
    }
    if (!$updated) $projects[] = $projectData;
    saveJson('data/projects.json', $projects);
    $_SESSION['admin_success'] = "Project saved successfully.";
    header('Location: admin.php?tab=projects');
    exit;
}

// Save site settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_site_settings'])) {
    $newSettings = $settings;
    $newSettings['site_title'] = $_POST['site_title'];
    $newSettings['hero_name'] = $_POST['hero_name'];
    $newSettings['hero_role'] = $_POST['hero_role'];
    $newSettings['hero_bio'] = $_POST['hero_bio'];
    $newSettings['stats_years'] = $_POST['stats_years'];
    $newSettings['stats_projects'] = $_POST['stats_projects'];
    $newSettings['stats_clients'] = $_POST['stats_clients'];
    $newSettings['discord_id'] = $_POST['discord_id'];
    $newSettings['github_url'] = $_POST['github_url'];
    $newSettings['linkedin_url'] = $_POST['linkedin_url'];
    
    if (!empty($_FILES['avatar']['name'])) {
        $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($ext, $allowed)) {
            $filename = 'uploads/avatar.' . $ext;
            move_uploaded_file($_FILES['avatar']['tmp_name'], $filename);
            $newSettings['avatar'] = $filename;
        }
    }
    saveJson('data/settings.json', $newSettings);
    $_SESSION['admin_success'] = "Site settings saved successfully.";
    header('Location: admin.php?tab=site');
    exit;
}

// Save SEO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_seo_settings'])) {
    $newSettings = $settings;
    $newSettings['seo_title'] = $_POST['seo_title'];
    $newSettings['seo_description'] = $_POST['seo_description'];
    $newSettings['seo_keywords'] = $_POST['seo_keywords'];
    saveJson('data/settings.json', $newSettings);
    $_SESSION['admin_success'] = "SEO settings saved successfully.";
    header('Location: admin.php?tab=seo');
    exit;
}

// Save Features
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_features'])) {
    $newFeatures = [];
    if (isset($_POST['feature_icon']) && is_array($_POST['feature_icon'])) {
        for ($i = 0; $i < count($_POST['feature_icon']); $i++) {
            if (!empty($_POST['feature_title'][$i])) {
                $newFeatures[] = [
                    'icon' => $_POST['feature_icon'][$i],
                    'title' => $_POST['feature_title'][$i],
                    'desc' => $_POST['feature_desc'][$i]
                ];
            }
        }
    }
    $settings['features'] = $newFeatures;
    saveJson('data/settings.json', $settings);
    $_SESSION['admin_success'] = "Features saved successfully.";
    header('Location: admin.php?tab=features');
    exit;
}

// Save Skills
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_skills'])) {
    $newSkills = [];
    if (isset($_POST['skill_name']) && is_array($_POST['skill_name'])) {
        for ($i = 0; $i < count($_POST['skill_name']); $i++) {
            if (!empty($_POST['skill_name'][$i])) {
                $newSkills[] = [
                    'name' => $_POST['skill_name'][$i],
                    'level' => (int)$_POST['skill_level'][$i],
                    'years' => (float)$_POST['skill_years'][$i]
                ];
            }
        }
    }
    $settings['skills'] = $newSkills;
    saveJson('data/settings.json', $settings);
    $_SESSION['admin_success'] = "Skills saved successfully.";
    header('Location: admin.php?tab=skills');
    exit;
}

// Save Languages
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_languages'])) {
    $newLanguages = [];
    if (isset($_POST['lang_code']) && is_array($_POST['lang_code'])) {
        for ($i = 0; $i < count($_POST['lang_code']); $i++) {
            if (!empty($_POST['lang_code'][$i]) && !empty($_POST['lang_name'][$i])) {
                $newLanguages[] = [
                    'code' => $_POST['lang_code'][$i],
                    'name' => $_POST['lang_name'][$i],
                    'flag' => $_POST['lang_flag'][$i]
                ];
            }
        }
    }
    $settings['languages'] = $newLanguages;
    saveJson('data/settings.json', $settings);
    $_SESSION['admin_success'] = "Languages saved successfully.";
    header('Location: admin.php?tab=languages');
    exit;
}

$editProject = null;
if (isset($_GET['edit_project'])) {
    foreach ($projects as $p) {
        if ($p['id'] == $_GET['edit_project']) $editProject = $p;
    }
}

$activeTab = $_GET['tab'] ?? 'projects';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="assets/global.css">
    <style>
        .admin-container { max-width: 1200px; margin: 100px auto 60px; padding: 0 24px; }
        .admin-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: 20px; padding: 32px; margin-bottom: 40px; }
        .admin-card h2 { margin-bottom: 24px; font-size: 24px; }
        .admin-form-group { margin-bottom: 20px; }
        .admin-form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: var(--text); }
        .admin-form-group input, .admin-form-group textarea, .admin-form-group select {
            width: 100%; padding: 12px; background: var(--bg); border: 1px solid var(--border);
            border-radius: 10px; color: var(--text); font-size: 14px;
        }
        .btn-save { background: var(--green); color: #000; padding: 12px 24px; border: none; border-radius: 10px; font-weight: 700; cursor: pointer; }
        .btn-danger { background: #dc2626; color: white; padding: 6px 12px; border-radius: 8px; text-decoration: none; }
        .project-list-item, .skill-list-item, .lang-list-item { background: var(--bg-card2); padding: 16px; border-radius: 12px; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
        .feature-row, .skill-row, .lang-row { background: var(--bg); padding: 16px; border-radius: 12px; margin-bottom: 16px; display: grid; gap: 12px; }
        .feature-row { grid-template-columns: 80px 1fr 1fr; }
        .skill-row { grid-template-columns: 1fr 100px 100px; }
        .lang-row { grid-template-columns: 100px 1fr 80px; }
        .gallery-preview { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 12px; }
        .gallery-preview img { width: 80px; height: 80px; object-fit: cover; border-radius: 8px; }
        @media (max-width: 768px) { .feature-row, .skill-row, .lang-row { grid-template-columns: 1fr; } }
        
        .admin-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 32px;
            flex-wrap: wrap;
            border-bottom: 1px solid var(--border);
            padding-bottom: 12px;
        }
        .admin-tab {
            padding: 10px 24px;
            background: var(--bg-card);
            border-radius: 12px;
            color: var(--muted);
            font-weight: 600;
            text-decoration: none;
        }
        .admin-tab.active { background: var(--primary); color: white; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        
        .toast {
            position: fixed;
            bottom: 20px;
            left: 20px;
            background: var(--bg-card);
            border-left: 4px solid var(--green);
            color: var(--text);
            padding: 12px 20px;
            border-radius: 12px;
            z-index: 9999;
            display: flex;
            gap: 12px;
            animation: slideIn 0.3s;
        }
        .toast.error { border-left-color: #dc2626; }
        .toast.hide { animation: fadeOut 0.3s forwards; }
        @keyframes slideIn { from { transform: translateX(-100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        @keyframes fadeOut { to { opacity: 0; visibility: hidden; } }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="navbar-logo"><span>⚙️</span><span>Admin Panel</span></div>
    <div class="navbar-links"><a href="index.php" target="_blank">View site</a></div>
    <div class="navbar-right"><a href="logout.php" style="color: #dc2626;">Logout</a></div>
</nav>

<div class="admin-container">
    <div class="admin-tabs">
        <a href="?tab=projects" class="admin-tab <?= $activeTab == 'projects' ? 'active' : '' ?>">📁 Projects</a>
        <a href="?tab=site" class="admin-tab <?= $activeTab == 'site' ? 'active' : '' ?>">⚙️ Site Settings</a>
        <a href="?tab=seo" class="admin-tab <?= $activeTab == 'seo' ? 'active' : '' ?>">🔍 SEO</a>
        <a href="?tab=features" class="admin-tab <?= $activeTab == 'features' ? 'active' : '' ?>">🎯 Features</a>
        <a href="?tab=skills" class="admin-tab <?= $activeTab == 'skills' ? 'active' : '' ?>">💻 Skills</a>
        <a href="?tab=languages" class="admin-tab <?= $activeTab == 'languages' ? 'active' : '' ?>">🌐 Languages</a>
    </div>

    <!-- Projects tab -->
    <div class="tab-content <?= $activeTab == 'projects' ? 'active' : '' ?>">
        <div class="admin-card">
            <h2><?= $editProject ? '✏️ Edit Project' : '➕ Add New Project' ?></h2>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?= $editProject['id'] ?? 'new' ?>">
                <?php if ($editProject && !empty($editProject['icon'])): ?>
                    <input type="hidden" name="old_icon" value="<?= htmlspecialchars($editProject['icon']) ?>">
                    <img src="<?= htmlspecialchars($editProject['icon']) ?>" style="max-width: 60px; margin-bottom: 16px;">
                <?php endif; ?>
                <div class="admin-form-group"><label>Title</label><input type="text" name="title" required value="<?= htmlspecialchars($editProject['title'] ?? '') ?>"></div>
                <div class="admin-form-group"><label>Short description</label><textarea name="short_desc" rows="2" required><?= htmlspecialchars($editProject['short_desc'] ?? '') ?></textarea></div>
                <div class="admin-form-group"><label>Date</label><input type="date" name="date" required value="<?= $editProject['date'] ?? date('Y-m-d') ?>"></div>
                <div class="admin-form-group"><label>Language</label>
                    <select name="language" required>
                        <?php foreach ($settings['languages'] ?? [] as $lang): ?>
                            <option value="<?= htmlspecialchars($lang['code']) ?>" <?= (($editProject['language'] ?? '') == $lang['code']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($lang['flag']) ?> <?= htmlspecialchars($lang['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="admin-form-group"><label>Icon (main image)</label><input type="file" name="icon" accept="image/*"></div>
                <div class="admin-form-group"><label>Gallery Images (multiple)</label><input type="file" name="gallery_images[]" accept="image/*" multiple></div>
                <?php if ($editProject && !empty($editProject['gallery'])): ?>
                    <div class="admin-form-group">
                        <label>Current Gallery Images</label>
                        <div class="gallery-preview">
                            <?php foreach ($editProject['gallery'] as $idx => $img): ?>
                                <div style="position: relative; display: inline-block;">
                                    <img src="<?= htmlspecialchars($img) ?>">
                                    <a href="?tab=projects&edit_project=<?= $editProject['id'] ?>&delete_gallery=<?= $idx ?>&project_id=<?= $editProject['id'] ?>" class="btn-danger" style="position: absolute; top: -8px; right: -8px; padding: 2px 6px; font-size: 10px;" onclick="return confirm('Delete this image?')">✕</a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="admin-form-group"><label>Markdown content</label><textarea name="content_md" rows="12" required><?= htmlspecialchars($editProject['content_md'] ?? '') ?></textarea></div>
                <button type="submit" name="save_project" class="btn-save">💾 Save Project</button>
                <?php if ($editProject): ?><a href="?tab=projects" style="margin-left: 16px;">← Cancel</a><?php endif; ?>
            </form>
        </div>
        <div class="admin-card">
            <h2>📋 All Projects (<?= count($projects) ?>)</h2>
            <?php if (empty($projects)): ?><p>No projects yet.</p>
            <?php else: foreach ($projects as $p): ?>
                <div class="project-list-item">
                    <div><strong><?= htmlspecialchars($p['title']) ?></strong><br><small><?= $p['date'] ?> | Lang: <?= htmlspecialchars($p['language'] ?? 'en') ?> | Gallery: <?= count($p['gallery'] ?? []) ?> images</small></div>
                    <div><a href="?tab=projects&edit_project=<?= $p['id'] ?>" style="color: var(--primary); margin-right: 16px;">✏️ Edit</a><a href="?tab=projects&delete_project=<?= $p['id'] ?>" class="btn-danger" onclick="return confirm('Delete?')">🗑️ Delete</a></div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <!-- Site Settings tab -->
    <div class="tab-content <?= $activeTab == 'site' ? 'active' : '' ?>">
        <div class="admin-card">
            <h2>⚙️ Site Settings</h2>
            <form method="post" enctype="multipart/form-data">
                <div class="admin-form-group"><label>Site Title</label><input type="text" name="site_title" value="<?= htmlspecialchars($settings['site_title'] ?? '') ?>"></div>
                <div class="admin-form-group"><label>Hero Name</label><input type="text" name="hero_name" value="<?= htmlspecialchars($settings['hero_name'] ?? '') ?>"></div>
                <div class="admin-form-group"><label>Hero Role</label><input type="text" name="hero_role" value="<?= htmlspecialchars($settings['hero_role'] ?? '') ?>"></div>
                <div class="admin-form-group"><label>Hero Bio</label><textarea name="hero_bio" rows="3"><?= htmlspecialchars($settings['hero_bio'] ?? '') ?></textarea></div>
                <div class="admin-form-group"><label>Avatar</label><?php if (!empty($settings['avatar']) && file_exists($settings['avatar'])): ?><div><img src="<?= htmlspecialchars($settings['avatar']) ?>" style="max-width: 80px;"></div><?php endif; ?><input type="file" name="avatar" accept="image/*"></div>
                <div class="admin-form-group"><label>Stats - Years</label><input type="text" name="stats_years" value="<?= htmlspecialchars($settings['stats_years'] ?? '3+') ?>"></div>
                <div class="admin-form-group"><label>Stats - Projects</label><input type="text" name="stats_projects" value="<?= htmlspecialchars($settings['stats_projects'] ?? '15') ?>"></div>
                <div class="admin-form-group"><label>Stats - Clients</label><input type="text" name="stats_clients" value="<?= htmlspecialchars($settings['stats_clients'] ?? '10') ?>"></div>
                <hr style="margin: 20px 0; border-color: var(--border);">
                <h3>🔗 Contact & Social</h3>
                <div class="admin-form-group"><label>Discord User ID</label><input type="text" name="discord_id" placeholder="123456789012345678" value="<?= htmlspecialchars($settings['discord_id'] ?? '') ?>"></div>
                <div class="admin-form-group"><label>GitHub URL</label><input type="url" name="github_url" placeholder="https://github.com/username" value="<?= htmlspecialchars($settings['github_url'] ?? '') ?>"></div>
                <div class="admin-form-group"><label>LinkedIn URL</label><input type="url" name="linkedin_url" placeholder="https://linkedin.com/in/username" value="<?= htmlspecialchars($settings['linkedin_url'] ?? '') ?>"></div>
                <button type="submit" name="save_site_settings" class="btn-save">💾 Save Settings</button>
            </form>
        </div>
    </div>

    <!-- SEO tab -->
    <div class="tab-content <?= $activeTab == 'seo' ? 'active' : '' ?>">
        <div class="admin-card">
            <h2>🔍 SEO Settings</h2>
            <form method="post">
                <div class="admin-form-group"><label>Meta Title</label><input type="text" name="seo_title" value="<?= htmlspecialchars($settings['seo_title'] ?? '') ?>"></div>
                <div class="admin-form-group"><label>Meta Description</label><textarea name="seo_description" rows="3"><?= htmlspecialchars($settings['seo_description'] ?? '') ?></textarea></div>
                <div class="admin-form-group"><label>Meta Keywords</label><input type="text" name="seo_keywords" value="<?= htmlspecialchars($settings['seo_keywords'] ?? '') ?>"></div>
                <button type="submit" name="save_seo_settings" class="btn-save">💾 Save SEO</button>
            </form>
        </div>
    </div>

    <!-- Features tab -->
    <div class="tab-content <?= $activeTab == 'features' ? 'active' : '' ?>">
        <div class="admin-card">
            <h2>🎯 Manage Features</h2>
            <form method="post">
                <div id="features-container">
                    <?php $features = $settings['features'] ?? []; if (empty($features)) $features = [['icon' => '', 'title' => '', 'desc' => '']];
                    foreach ($features as $f): ?>
                        <div class="feature-row">
                            <input type="text" name="feature_icon[]" placeholder="Icon" value="<?= htmlspecialchars($f['icon'] ?? '') ?>">
                            <input type="text" name="feature_title[]" placeholder="Title" value="<?= htmlspecialchars($f['title'] ?? '') ?>">
                            <input type="text" name="feature_desc[]" placeholder="Description" value="<?= htmlspecialchars($f['desc'] ?? '') ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" onclick="addFeature()" style="background: var(--bg-card); padding: 8px 16px; border-radius: 8px; margin-bottom: 24px;">+ Add Feature</button>
                <button type="submit" name="save_features" class="btn-save">💾 Save Features</button>
            </form>
        </div>
    </div>

    <!-- Skills tab -->
    <div class="tab-content <?= $activeTab == 'skills' ? 'active' : '' ?>">
        <div class="admin-card">
            <h2>💻 Manage Skills</h2>
            <form method="post">
                <div id="skills-container">
                    <?php $skills = $settings['skills'] ?? []; if (empty($skills)) $skills = [['name' => '', 'level' => 50, 'years' => 1]];
                    foreach ($skills as $s): ?>
                        <div class="skill-row">
                            <input type="text" name="skill_name[]" placeholder="Skill name" value="<?= htmlspecialchars($s['name'] ?? '') ?>">
                            <input type="number" name="skill_level[]" placeholder="Level %" min="0" max="100" value="<?= $s['level'] ?? 50 ?>">
                            <input type="number" name="skill_years[]" placeholder="Years" min="0" step="0.5" value="<?= $s['years'] ?? 1 ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" onclick="addSkill()" style="background: var(--bg-card); padding: 8px 16px; border-radius: 8px; margin-bottom: 24px;">+ Add Skill</button>
                <button type="submit" name="save_skills" class="btn-save">💾 Save Skills</button>
            </form>
        </div>
        <div class="admin-card">
            <h3>Current Skills</h3>
            <?php if (empty($settings['skills'])): ?><p>No skills added.</p>
            <?php else: foreach ($settings['skills'] as $idx => $s): ?>
                <div class="skill-list-item">
                    <div><strong><?= htmlspecialchars($s['name']) ?></strong> — <?= $s['level'] ?>% (<?= $s['years'] ?> years)</div>
                    <div><a href="?tab=skills&delete_skill=<?= $idx ?>" class="btn-danger" onclick="return confirm('Delete?')">🗑️ Delete</a></div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <!-- Languages tab -->
    <div class="tab-content <?= $activeTab == 'languages' ? 'active' : '' ?>">
        <div class="admin-card">
            <h2>🌐 Manage Languages</h2>
            <form method="post">
                <div id="languages-container">
                    <?php $languages = $settings['languages'] ?? []; if (empty($languages)) $languages = [['code' => '', 'name' => '', 'flag' => '']];
                    foreach ($languages as $lang): ?>
                        <div class="lang-row">
                            <input type="text" name="lang_code[]" placeholder="Code (en, lt)" value="<?= htmlspecialchars($lang['code'] ?? '') ?>">
                            <input type="text" name="lang_name[]" placeholder="Name (English, Lietuvių)" value="<?= htmlspecialchars($lang['name'] ?? '') ?>">
                            <input type="text" name="lang_flag[]" placeholder="Flag emoji" value="<?= htmlspecialchars($lang['flag'] ?? '') ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" onclick="addLanguage()" style="background: var(--bg-card); padding: 8px 16px; border-radius: 8px; margin-bottom: 24px;">+ Add Language</button>
                <button type="submit" name="save_languages" class="btn-save">💾 Save Languages</button>
            </form>
        </div>
        <div class="admin-card">
            <h3>Current Languages</h3>
            <?php if (empty($settings['languages'])): ?><p>No languages added.</p>
            <?php else: foreach ($settings['languages'] as $idx => $lang): ?>
                <div class="lang-list-item">
                    <div><?= htmlspecialchars($lang['flag']) ?> <strong><?= htmlspecialchars($lang['name']) ?></strong> (<?= htmlspecialchars($lang['code']) ?>)</div>
                    <div><a href="?tab=languages&delete_language=<?= $idx ?>" class="btn-danger" onclick="return confirm('Delete this language?')">🗑️ Delete</a></div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>

<script>
function addFeature() {
    const container = document.getElementById('features-container');
    const div = document.createElement('div'); div