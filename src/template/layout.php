<?php

function render_page(string $pageFile, array $data = []): void
{
    foreach ($data as $key => $value) {
        $data[$key] = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    extract($data, EXTR_SKIP);

    $title = $data['title'] ?? 'Finance App';
    $pagePath = __DIR__ . "/../pages/$pageFile";
    
    if (!preg_match('/^[a-z0-9_-]+\.php$/i', $pageFile) || !file_exists($pagePath)) {
        http_response_code(404);
        echo "Page not found";
        die();
    }

    ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <link rel="stylesheet" href="/assets/style.css?<?= random_int(0, PHP_INT_MAX)?>">
</head>
<body>
    <?php require $pagePath; ?>

    <script src="/assets/script.js?<?= random_int(0, PHP_INT_MAX)?>"></script>
</body>
</html>
<?php
}