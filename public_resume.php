<?php
require_once 'config.php';

// Get user ID from URL parameter, default to 1 if not provided
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 1;

if ($user_id <= 0) {
    $user_id = 1; // Default to user ID 1
}

try {
    // Get user data
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        die("User not found");
    }
    
    // Get personal information
    $stmt = $pdo->prepare("SELECT field_name, field_value FROM personal_info WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $personal_info = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $personal_info[$row['field_name']] = $row['field_value'];
    }
    
    // Get education
    $stmt = $pdo->prepare("SELECT degree, school, dates FROM education WHERE user_id = ? ORDER BY id");
    $stmt->execute([$user_id]);
    $education = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get skills
    $stmt = $pdo->prepare("SELECT skill_name FROM skills WHERE user_id = ? ORDER BY id");
    $stmt->execute([$user_id]);
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
    $stmt->execute([$user_id]);
    $projects = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $projects[] = [
            'name' => $row['project_name'],
            'link' => $row['project_link'],
            'tech' => $row['technologies'] ? explode(',', $row['technologies']) : []
        ];
    }
    
    // Get languages
    $languages = explode(',', $user['languages']);
    
} catch(PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?php echo htmlspecialchars($user['name']); ?> — Resume</title>
    <style>
        :root{
            --accent:#0b76ef;
            --muted:#666;
        }
        
        body{
            font-family:Inter,Segoe UI,Roboto,Arial,sans-serif;
            margin:0;
            background:#f7f8fb;
            color:#222;
        }
        
        .container{
            max-width:900px;
            margin:32px auto;
            background:#fff;
            box-shadow:0 6px 18px rgba(20,20,40,.06);
            border-radius:10px;
            overflow:hidden;
        }
        
        header{
            display:flex;
            gap:24px;
            padding:28px;
            align-items:center;
        }
        
        .avatar img{
            width:96px;
            height:96px;
            border-radius:8px;
            object-fit:cover;
            box-shadow:0 2px 8px rgba(0,0,0,0.1);
        }
        
        .hgroup h1{
            margin:0;
            font-size:22px;
        }
        
        .hgroup p{
            margin:6px 0 0;
            color:var(--muted);
        }
        
        .contact-icons{
            margin-top:8px;
            font-size:14px;
            color:var(--muted);
        }
        
        .contact-icons span{
            display:inline-flex;
            align-items:center;
            margin-right:16px;
        }
        
        .contact-icons img{
            width:14px;
            height:14px;
            margin-right:6px;
            opacity:0.8;
        }
        
        .main{
            display:grid;
            grid-template-columns:1fr 320px;
            gap:24px;
            padding:0 28px 28px;
        }
        
        .section{
            padding:18px 0;
            border-top:1px solid #f0f2f5;
        }
        
        h2{
            margin:0 0 12px;
            font-size:16px;
            color:var(--accent);
        }
        
        ul{
            margin:0;
            padding:0;
            list-style:none;
        }
        
        li{
            margin-bottom:12px;
        }
        
        .meta{
            color:var(--muted);
            font-size:13px;
        }
        
        .badge{
            display:inline-block;
            padding:6px 8px;
            border-radius:6px;
            background:#f1f7ff;
            margin:4px 6px 0 0;
            font-size:13px;
        }
        
        .right{
            background:#fbfdff;
            padding:18px;
            border-left:1px solid #f0f2f5;
        }
        
        .project-link{
            font-size:12px;
            display:inline-flex;
            align-items:center;
            color:var(--accent);
            margin-top:4px;
        }
        
        .project-link img{
            width:14px;
            height:14px;
            margin-left:4px;
            vertical-align:middle;
        }
        
        a{
            color:var(--accent);
            text-decoration:none;
        }
        
        .login-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #007BFF;
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            z-index: 1000;
            transition: all 0.3s;
            box-shadow: 0 2px 8px rgba(0,123,255,0.3);
        }
        
        .login-btn:hover {
            background: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,123,255,0.4);
        }
        
        @media(max-width:800px){
            .main{
                grid-template-columns:1fr;
            }
            .right{
                border-left:none;
                border-top:1px solid #f0f2f5;
            }
            .login-btn {
                position: static;
                display: inline-block;
                margin: 20px;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <a href="login.php" class="login-btn">🔐 Login to Edit</a>
    
    <div class="container">
        <header>
        <div class="avatar">
            <?php 
            $photo_url = htmlspecialchars($user['photo']);
            $fallback_svg = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iOTYiIGhlaWdodD0iOTYiIHZpZXdCb3g9IjAgMCA5NiA5NiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9Ijk2IiBoZWlnaHQ9Ijk2IiBmaWxsPSIjRjBGMEYwIi8+CjxjaXJjbGUgY3g9IjQ4IiBjeT0iMzYiIHI9IjE4IiBmaWxsPSIjQ0NDQ0NDIi8+CjxwYXRoIGQ9Ik0yNCA4MkMyNCA3Mi4wNTg2IDMyLjA1ODYgNjQgNDIgNjRINjRDNzMuOTQxNCA2NCA4MiA3Mi4wNTg2IDgyIDgyVjgySDI0VjgyWiIgZmlsbD0iI0NDQ0NDQyIvPgo8L3N2Zz4K';
            
            if (file_exists($photo_url)) {
                echo '<img src="' . $photo_url . '" alt="Profile Photo" onerror="this.src=\'' . $fallback_svg . '\'">';
            } else {
                echo '<img src="' . $fallback_svg . '" alt="Profile Photo">';
            }
            ?>
        </div>
            <div class="hgroup">
                <h1><?php echo htmlspecialchars($user['name']); ?></h1>
                <p><?php echo htmlspecialchars($user['title']); ?> · <span class="meta"><?php echo htmlspecialchars($user['location']); ?></span></p>
                <div class="contact-icons">
                    <span><img src="https://img.icons8.com/ios-filled/50/000000/gmail.png" alt="Gmail"><?php echo htmlspecialchars($user['email']); ?></span>
                    <?php if ($user['phone']): ?>
                    <span><img src="https://img.icons8.com/ios-filled/50/000000/phone.png" alt="Phone"><?php echo htmlspecialchars($user['phone']); ?></span>
                    <?php endif; ?>
                    <?php if ($user['github']): ?>
                    <span><img src="https://img.icons8.com/ios-filled/50/000000/github.png" alt="GitHub"><a href="<?php echo htmlspecialchars($user['github']); ?>" target="_blank">GitHub Profile</a></span>
                    <?php endif; ?>
                </div>
            </div>
        </header>
        
        <div class="main">
            <main>
                <?php if (!empty($personal_info)): ?>
                <section class="section">
                    <h2>Personal Information</h2>
                    <ul>
                        <?php foreach($personal_info as $key => $value): ?>
                            <?php if (!empty($value)): ?>
                            <li><strong><?php echo htmlspecialchars($key); ?>:</strong> <?php echo htmlspecialchars($value); ?></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </section>
                <?php endif; ?>
                
                <?php if ($user['summary']): ?>
                <section class="section">
                    <h2>Summary</h2>
                    <p><?php echo htmlspecialchars($user['summary']); ?></p>
                </section>
                <?php endif; ?>
                
                <?php if (!empty($education)): ?>
                <section class="section">
                    <h2>Education</h2>
                    <ul>
                        <?php foreach($education as $ed): ?>
                            <?php if (!empty($ed['school']) && !empty($ed['dates'])): ?>
                            <li>
                                <strong><?php echo htmlspecialchars($ed['degree']); ?></strong>
                                <div class="meta"><?php echo htmlspecialchars($ed['school']); ?> · <?php echo htmlspecialchars($ed['dates']); ?></div>
                            </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </section>
                <?php endif; ?>
                
                <?php if (!empty($languages) && !empty(array_filter($languages))): ?>
                <section class="section">
                    <h2>Languages</h2>
                    <div>
                        <?php foreach($languages as $lang): ?>
                            <?php if (trim($lang)): ?>
                            <span class="badge"><?php echo htmlspecialchars(trim($lang)); ?></span>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>
            </main>
            
            <aside class="right">
                <?php if (!empty($skills) && !empty(array_filter($skills))): ?>
                <section class="section">
                    <h2>Skills</h2>
                    <div>
                        <?php foreach($skills as $s): ?>
                            <?php if (trim($s)): ?>
                            <span class="badge"><?php echo htmlspecialchars(trim($s)); ?></span>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>
                
                <?php if (!empty($projects)): ?>
                <section class="section">
                    <h2>Projects</h2>
                    <ul>
                        <?php foreach($projects as $p): ?>
                            <?php if (!empty($p['name'])): ?>
                            <li>
                                <strong><?php echo htmlspecialchars($p['name']); ?></strong>
                                <?php if (!empty($p['tech'])): ?>
                                    <?php foreach($p['tech'] as $tech): ?>
                                        <?php if (trim($tech)): ?>
                                        <span class="badge"><?php echo htmlspecialchars(trim($tech)); ?></span>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                <?php if (!empty($p['link'])): ?>
                                <a class="project-link" href="<?php echo htmlspecialchars($p['link']); ?>" target="_blank">
                                    View on <img src="https://img.icons8.com/ios-filled/50/000000/github.png" alt="GitHub">
                                </a>
                                <?php endif; ?>
                            </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </section>
                <?php endif; ?>
            </aside>
        </div>
    </div>
</body>
</html>
