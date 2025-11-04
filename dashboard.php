<?php
session_start();
require_once 'config.php';

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: public_resume.php?id=1");
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

$success_message = "";
$error_message = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        $user_id = $_SESSION['user_id'];
        
        // Validate required fields
        $name = trim($_POST['name']);
        $title = trim($_POST['title']);
        $email = trim($_POST['email']);
        
        if (empty($name) || empty($title) || empty($email)) {
            $error_message = "Name, title, and email are required fields.";
        } else {
            // Start transaction
            $pdo->beginTransaction();
            
            try {
                // Determine photo value: keep existing when input is empty
                $currentPhotoStmt = $pdo->prepare("SELECT photo FROM users WHERE id = ?");
                $currentPhotoStmt->execute([$user_id]);
                $currentPhotoRow = $currentPhotoStmt->fetch(PDO::FETCH_ASSOC);
                $inputPhoto = isset($_POST['photo']) ? trim($_POST['photo']) : '';
                $photoToSave = ($inputPhoto === '') ? ($currentPhotoRow['photo'] ?? '') : $inputPhoto;

                // Update user data
                $stmt = $pdo->prepare("
                    UPDATE users SET 
                        name = ?, title = ?, photo = ?, email = ?, phone = ?, 
                        location = ?, github = ?, summary = ?, languages = ?, 
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");
                
                $stmt->execute([
                    $name,
                    $title,
                    $photoToSave,
                    $email,
                    $_POST['phone'],
                    $_POST['location'],
                    $_POST['github'],
                    $_POST['summary'],
                    $_POST['languages'],
                    $user_id
                ]);
                
                // Update personal information
                $personal_fields = [
                    'Date of Birth' => $_POST['date_of_birth'],
                    'Place of Birth' => $_POST['place_of_birth'],
                    'Civil Status' => $_POST['civil_status'],
                    'Field of Specialization' => $_POST['field_of_specialization']
                ];
                
                // Delete existing personal info
                $stmt = $pdo->prepare("DELETE FROM personal_info WHERE user_id = ?");
                $stmt->execute([$user_id]);
                
                // Insert new personal info
                $stmt = $pdo->prepare("INSERT INTO personal_info (user_id, field_name, field_value) VALUES (?, ?, ?)");
                foreach ($personal_fields as $field_name => $field_value) {
                    if (!empty($field_value)) {
                        $stmt->execute([$user_id, $field_name, $field_value]);
                    }
                }
                
                // Update education
                $education_data = [
                    ['Elementary', $_POST['elementary_school'], $_POST['elementary_dates']],
                    ['Secondary', $_POST['secondary_school'], $_POST['secondary_dates']],
                    ['Tertiary', $_POST['tertiary_school'], $_POST['tertiary_dates']]
                ];
                
                // Delete existing education
                $stmt = $pdo->prepare("DELETE FROM education WHERE user_id = ?");
                $stmt->execute([$user_id]);
                
                // Insert new education
                $stmt = $pdo->prepare("INSERT INTO education (user_id, degree, school, dates) VALUES (?, ?, ?, ?)");
                foreach ($education_data as $edu) {
                    if (!empty($edu[1]) && !empty($edu[2])) {
                        $stmt->execute([$user_id, $edu[0], $edu[1], $edu[2]]);
                    }
                }
                
                // Update skills
                $skills = array_filter(array_map('trim', explode(',', $_POST['skills'])));
                
                // Delete existing skills
                $stmt = $pdo->prepare("DELETE FROM skills WHERE user_id = ?");
                $stmt->execute([$user_id]);
                
                // Insert new skills
                $stmt = $pdo->prepare("INSERT INTO skills (user_id, skill_name) VALUES (?, ?)");
                foreach ($skills as $skill) {
                    if (!empty($skill)) {
                        $stmt->execute([$user_id, $skill]);
                    }
                }
                
                // Update projects
                $projects_data = [
                    [$_POST['project1_name'], $_POST['project1_link'], $_POST['project1_tech']],
                    [$_POST['project2_name'], $_POST['project2_link'], $_POST['project2_tech']],
                    [$_POST['project3_name'], $_POST['project3_link'], $_POST['project3_tech']]
                ];
                
                // Delete existing projects and technologies
                $stmt = $pdo->prepare("DELETE FROM project_technologies WHERE project_id IN (SELECT id FROM projects WHERE user_id = ?)");
                $stmt->execute([$user_id]);
                $stmt = $pdo->prepare("DELETE FROM projects WHERE user_id = ?");
                $stmt->execute([$user_id]);
                
                // Insert new projects
                $stmt = $pdo->prepare("INSERT INTO projects (user_id, project_name, project_link) VALUES (?, ?, ?)");
                $tech_stmt = $pdo->prepare("INSERT INTO project_technologies (project_id, technology) VALUES (?, ?)");
                
                foreach ($projects_data as $project) {
                    if (!empty($project[0])) {
                        $stmt->execute([$user_id, $project[0], $project[1]]);
                        $project_id = $pdo->lastInsertId();
                        
                        // Insert technologies
                        $technologies = array_filter(array_map('trim', explode(',', $project[2])));
                        foreach ($technologies as $tech) {
                            if (!empty($tech)) {
                                $tech_stmt->execute([$project_id, $tech]);
                            }
                        }
                    }
                }
                
                // Commit transaction
                $pdo->commit();
                $success_message = "Resume updated successfully!";
                
            } catch (Exception $e) {
                // Rollback transaction on error
                $pdo->rollback();
                throw $e;
            }
        }
    } catch(PDOException $e) {
        $error_message = "Database error: " . $e->getMessage();
    }
}

// Get current user data
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        header("Location: login.php");
        exit;
    }
    
    // Get personal information
    $stmt = $pdo->prepare("SELECT field_name, field_value FROM personal_info WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $personal_info = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $personal_info[$row['field_name']] = $row['field_value'];
    }
    
    // Get education
    $stmt = $pdo->prepare("SELECT degree, school, dates FROM education WHERE user_id = ? ORDER BY id");
    $stmt->execute([$_SESSION['user_id']]);
    $education = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get skills
    $stmt = $pdo->prepare("SELECT skill_name FROM skills WHERE user_id = ? ORDER BY id");
    $stmt->execute([$_SESSION['user_id']]);
    $skills = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Get projects with technologies
    $stmt = $pdo->prepare("
        SELECT p.id, p.project_name, p.project_link, 
               STRING_AGG(pt.technology, ',' ORDER BY pt.technology) as technologies
        FROM projects p 
        LEFT JOIN project_technologies pt ON p.id = pt.project_id 
        WHERE p.user_id = ? 
        GROUP BY p.id, p.project_name, p.project_link
        ORDER BY p.id
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $projects = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $projects[] = [
            'name' => $row['project_name'],
            'link' => $row['project_link'],
            'tech' => $row['technologies'] ? explode(',', $row['technologies']) : []
        ];
    }
    
} catch(PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard - Edit Resume</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            color: #333;
        }
        
        .header {
            background: #2c3e50;
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            font-size: 1.5rem;
        }
        
        .header a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            background: #e74c3c;
            border-radius: 4px;
            transition: background 0.3s;
        }
        
        .header a:hover {
            background: #c0392b;
        }
        
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 4px;
            font-weight: 500;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .form-container {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #555;
        }
        
        input, textarea, select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }
        
        textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .btn {
            background: #3498db;
            color: white;
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #2980b9;
        }
        
        .btn-secondary {
            background: #95a5a6;
            margin-left: 1rem;
        }
        
        .btn-secondary:hover {
            background: #7f8c8d;
        }
        
        .section-title {
            font-size: 1.25rem;
            color: #2c3e50;
            margin: 2rem 0 1rem 0;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #ecf0f1;
        }
        
        .public-link {
            background: #27ae60;
            color: white;
            padding: 0.5rem 1rem;
            text-decoration: none;
            border-radius: 4px;
            display: inline-block;
            margin-bottom: 1rem;
        }
        
        .public-link:hover {
            background: #229954;
        }
        
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .header {
                padding: 1rem;
            }
            
            .container {
                margin: 1rem auto;
                padding: 0 0.5rem;
            }
        }
    </style>
    <script>
        function updatePhotoPreview(url) {
            const preview = document.getElementById('photo-preview');
            const fallbackSvg = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iOTYiIGhlaWdodD0iOTYiIHZpZXdCb3g9IjAgMCA5NiA5NiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9Ijk2IiBoZWlnaHQ9Ijk2IiBmaWxsPSIjRjBGMEYwIi8+CjxjaXJjbGUgY3g9IjQ4IiBjeT0iMzYiIHI9IjE4IiBmaWxsPSIjQ0NDQ0NDIi8+CjxwYXRoIGQ9Ik0yNCA4MkMyNCA3Mi4wNTg2IDMyLjA1ODYgNjQgNDIgNjRINjRDNzMuOTQxNCA2NCA4MiA3Mi4wNTg2IDgyIDgyVjgySDI0VjgyWiIgZmlsbD0iI0NDQ0NDQyIvPgo8L3N2Zz4K';
            
            if (url && url.trim() !== '') {
                preview.innerHTML = '<img src="' + url + '" alt="Photo Preview" style="width: 96px; height: 96px; border-radius: 8px; object-fit: cover; border: 1px solid #ddd;" onerror="this.src=\'' + fallbackSvg + '\'">';
            } else {
                preview.innerHTML = '<img src="' + fallbackSvg + '" alt="Photo Preview" style="width: 96px; height: 96px; border-radius: 8px; border: 1px solid #ddd;">';
            }
        }
    </script>
</head>
<body>
    <div class="header">
        <h1>Dashboard - Edit Resume</h1>
        <a href="?logout=true">Logout</a>
    </div>
    
    <div class="container">
        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <a href="public_resume.php?id=<?php echo $user['id']; ?>" class="public-link" target="_blank">
            View Public Resume
        </a>
        
        <div class="form-container">
            <form method="POST" action="">
                <h2 class="section-title">Basic Information</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="name">Full Name *</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="title">Title/Position *</label>
                        <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($user['title']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="location">Location</label>
                        <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($user['location']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="github">GitHub URL</label>
                        <input type="url" id="github" name="github" value="<?php echo htmlspecialchars($user['github']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="photo">Photo URL or Filename</label>
                        <input type="text" id="photo" name="photo" value="<?php echo htmlspecialchars($user['photo']); ?>" placeholder="https://example.com/photo.jpg or paul-photo.jpg" onchange="updatePhotoPreview(this.value)">
                        <small style="color: #666; font-size: 12px;">Enter a full URL or a local filename (e.g., paul-photo.jpg). Leave blank to keep the current photo.</small>
                        <div id="photo-preview" style="margin-top: 10px;">
                            <?php 
                            $photo_url = htmlspecialchars($user['photo']);
                            $fallback_svg = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iOTYiIGhlaWdodD0iOTYiIHZpZXdCb3g9IjAgMCA5NiA5NiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9Ijk2IiBoZWlnaHQ9Ijk2IiBmaWxsPSIjRjBGMEYwIi8+CjxjaXJjbGUgY3g9IjQ4IiBjeT0iMzYiIHI9IjE4IiBmaWxsPSIjQ0NDQ0NDIi8+CjxwYXRoIGQ9Ik0yNCA4MkMyNCA3Mi4wNTg2IDMyLjA1ODYgNjQgNDIgNjRINjRDNzMuOTQxNCA2NCA4MiA3Mi4wNTg2IDgyIDgyVjgySDI0VjgyWiIgZmlsbD0iI0NDQ0NDQyIvPgo8L3N2Zz4K';
                            
                            // Support absolute URLs and local filenames in project directory
                            $isUrl = preg_match('/^https?:\/\//i', $photo_url);
                            $localPath = __DIR__ . DIRECTORY_SEPARATOR . $photo_url;
                            if (!empty($photo_url) && ($isUrl || file_exists($localPath))) {
                                $src = $isUrl ? $photo_url : $photo_url; // for local files, the browser can resolve relative path
                                echo '<img src="' . $src . '" alt="Photo Preview" style="width: 96px; height: 96px; border-radius: 8px; object-fit: cover; border: 1px solid #ddd;">';
                            } else {
                                echo '<img src="' . $fallback_svg . '" alt="Photo Preview" style="width: 96px; height: 96px; border-radius: 8px; border: 1px solid #ddd;">';
                            }
                            ?>
                        </div>
                    </div>
                </div>
                
                <div class="form-group full-width">
                    <label for="summary">Professional Summary</label>
                    <textarea id="summary" name="summary" rows="4"><?php echo htmlspecialchars($user['summary']); ?></textarea>
                </div>
                
                <h2 class="section-title">Skills & Languages</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="skills">Skills (comma-separated)</label>
                        <input type="text" id="skills" name="skills" value="<?php echo htmlspecialchars(implode(', ', $skills)); ?>" placeholder="PHP, HTML, CSS, JavaScript">
                    </div>
                    <div class="form-group">
                        <label for="languages">Languages (comma-separated)</label>
                        <input type="text" id="languages" name="languages" value="<?php echo htmlspecialchars($user['languages']); ?>" placeholder="English, Filipino">
                    </div>
                </div>
                
                <h2 class="section-title">Personal Information</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="date_of_birth">Date of Birth</label>
                        <input type="text" id="date_of_birth" name="date_of_birth" value="<?php echo htmlspecialchars($personal_info['Date of Birth'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="place_of_birth">Place of Birth</label>
                        <input type="text" id="place_of_birth" name="place_of_birth" value="<?php echo htmlspecialchars($personal_info['Place of Birth'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="civil_status">Civil Status</label>
                        <select id="civil_status" name="civil_status">
                            <option value="Single" <?php echo ($personal_info['Civil Status'] ?? '') === 'Single' ? 'selected' : ''; ?>>Single</option>
                            <option value="Married" <?php echo ($personal_info['Civil Status'] ?? '') === 'Married' ? 'selected' : ''; ?>>Married</option>
                            <option value="Divorced" <?php echo ($personal_info['Civil Status'] ?? '') === 'Divorced' ? 'selected' : ''; ?>>Divorced</option>
                            <option value="Widowed" <?php echo ($personal_info['Civil Status'] ?? '') === 'Widowed' ? 'selected' : ''; ?>>Widowed</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="field_of_specialization">Field of Specialization</label>
                        <input type="text" id="field_of_specialization" name="field_of_specialization" value="<?php echo htmlspecialchars($personal_info['Field of Specialization'] ?? ''); ?>">
                    </div>
                </div>
                
                <h2 class="section-title">Education</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="elementary_school">Elementary School</label>
                        <input type="text" id="elementary_school" name="elementary_school" value="<?php echo htmlspecialchars($education[0]['school'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="elementary_dates">Elementary Dates</label>
                        <input type="text" id="elementary_dates" name="elementary_dates" value="<?php echo htmlspecialchars($education[0]['dates'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="secondary_school">Secondary School</label>
                        <input type="text" id="secondary_school" name="secondary_school" value="<?php echo htmlspecialchars($education[1]['school'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="secondary_dates">Secondary Dates</label>
                        <input type="text" id="secondary_dates" name="secondary_dates" value="<?php echo htmlspecialchars($education[1]['dates'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="tertiary_school">Tertiary School</label>
                        <input type="text" id="tertiary_school" name="tertiary_school" value="<?php echo htmlspecialchars($education[2]['school'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="tertiary_dates">Tertiary Dates</label>
                        <input type="text" id="tertiary_dates" name="tertiary_dates" value="<?php echo htmlspecialchars($education[2]['dates'] ?? ''); ?>">
                    </div>
                </div>
                
                <h2 class="section-title">Projects</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="project1_name">Project 1 Name</label>
                        <input type="text" id="project1_name" name="project1_name" value="<?php echo htmlspecialchars($projects[0]['name'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="project1_link">Project 1 Link</label>
                        <input type="url" id="project1_link" name="project1_link" value="<?php echo htmlspecialchars($projects[0]['link'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="project1_tech">Project 1 Technologies</label>
                        <input type="text" id="project1_tech" name="project1_tech" value="<?php echo htmlspecialchars(implode(', ', $projects[0]['tech'] ?? [])); ?>" placeholder="PHP, MySQL">
                    </div>
                    <div class="form-group">
                        <label for="project2_name">Project 2 Name</label>
                        <input type="text" id="project2_name" name="project2_name" value="<?php echo htmlspecialchars($projects[1]['name'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="project2_link">Project 2 Link</label>
                        <input type="url" id="project2_link" name="project2_link" value="<?php echo htmlspecialchars($projects[1]['link'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="project2_tech">Project 2 Technologies</label>
                        <input type="text" id="project2_tech" name="project2_tech" value="<?php echo htmlspecialchars(implode(', ', $projects[1]['tech'] ?? [])); ?>" placeholder="Python, JavaScript">
                    </div>
                    <div class="form-group">
                        <label for="project3_name">Project 3 Name</label>
                        <input type="text" id="project3_name" name="project3_name" value="<?php echo htmlspecialchars($projects[2]['name'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="project3_link">Project 3 Link</label>
                        <input type="url" id="project3_link" name="project3_link" value="<?php echo htmlspecialchars($projects[2]['link'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="project3_tech">Project 3 Technologies</label>
                        <input type="text" id="project3_tech" name="project3_tech" value="<?php echo htmlspecialchars(implode(', ', $projects[2]['tech'] ?? [])); ?>" placeholder="HTML, CSS, MySQL">
                    </div>
                </div>
                
                <div style="margin-top: 2rem;">
                    <button type="submit" class="btn">Update Resume</button>
                    <a href="public_resume.php?id=<?php echo $user['id']; ?>" class="btn btn-secondary" target="_blank">Preview Public View</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
